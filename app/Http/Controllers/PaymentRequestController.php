<?php

namespace App\Http\Controllers;

use App\Enums\PaymentRequestStatus;
use App\Enums\PaymentType;
use App\Http\Requests\PaymentRequests\ApprovePaymentRequestRequest;
use App\Http\Requests\PaymentRequests\AssignPaymentRequestRequest;
use App\Http\Requests\PaymentRequests\CommentPaymentRequestRequest;
use App\Http\Requests\PaymentRequests\MarkPaidRequest;
use App\Http\Requests\PaymentRequests\RejectPaymentRequestRequest;
use App\Http\Requests\PaymentRequests\ReturnPaymentRequestRequest;
use App\Http\Requests\PaymentRequests\StorePaymentRequestRequest;
use App\Http\Requests\PaymentRequests\UpdatePaymentRequestRequest;
use App\Http\Requests\PaymentRequests\UploadPaymentRequestDocumentRequest;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestDocument;
use App\Models\User;
use App\Notifications\PaymentRequestUpdated;
use App\Services\PaymentRequestWorkflowService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentRequestController extends Controller
{
    public function __construct(private readonly PaymentRequestWorkflowService $workflow)
    {
    }

    /**
     * Directory listing - Admin sees every request; Staff see only what
     * they created, currently hold, or have ever touched via an
     * assignment (so a request they forwarded onward doesn't just
     * disappear from their view).
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', PaymentRequest::class);

        $user = $request->user();

        $query = PaymentRequest::query()->with(['creator', 'currentAssignee']);

        if (! $user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                    ->orWhere('current_assignee_id', $user->id)
                    ->orWhereHas('assignments', fn ($a) => $a->where('assigned_to', $user->id)->orWhere('assigned_by', $user->id));
            });
        }

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                    ->orWhere('payee_name', 'like', "%{$search}%");
            });
        }

        if ($type = $request->string('type')->value()) {
            $query->where('payment_type', $type);
        }

        if ($status = $request->string('status')->value()) {
            $query->where('status', $status);
        }

        if ($from = $request->date('date_from')) {
            $query->whereDate('payment_date', '>=', $from);
        }

        if ($to = $request->date('date_to')) {
            $query->whereDate('payment_date', '<=', $to);
        }

        $paymentRequests = $query->latest('created_at')->paginate(15)->withQueryString();

        return view('payment-requests.index', [
            'paymentRequests' => $paymentRequests,
            'types' => PaymentType::options(),
            'statuses' => PaymentRequestStatus::options(),
            'isAdmin' => $user->isAdmin(),
        ]);
    }

    /**
     * Distinct from index(): only requests currently sitting with this
     * user awaiting their action - not everything they've ever touched.
     */
    public function myTasks(Request $request): View
    {
        $user = $request->user();

        $tasks = PaymentRequest::query()
            ->awaitingActionFrom($user->id)
            ->with(['creator'])
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('payment-requests.my-tasks', ['tasks' => $tasks]);
    }

    public function typePicker(): View
    {
        $this->authorize('create', PaymentRequest::class);

        return view('payment-requests.type-picker', ['types' => PaymentType::cases()]);
    }

    public function create(string $type): View
    {
        $this->authorize('create', PaymentRequest::class);

        return view('payment-requests.create', [
            'type' => PaymentType::fromRouteSegment($type),
            'currencies' => config('payment_requests.supported_currencies'),
            'defaultCurrency' => config('payment_requests.default_currency'),
            'defaultWht' => config('payment_requests.default_withholding_tax_percentage'),
        ]);
    }

    public function store(StorePaymentRequestRequest $request, string $type): RedirectResponse
    {
        $type = PaymentType::fromRouteSegment($type);

        $paymentRequest = $this->workflow->create($request->user(), $type, $request->validated());

        if ($request->input('form_action') === 'submit') {
            return $this->submitAfterSave($request, $paymentRequest);
        }

        return redirect()->route('payment-requests.show', $paymentRequest)
            ->with('success', 'Draft saved. You can keep editing it before submitting.');
    }

    public function show(PaymentRequest $paymentRequest): View
    {
        $this->authorize('view', $paymentRequest);

        $paymentRequest->load([
            'creator', 'currentAssignee', 'approver', 'processor', 'rejecter',
            'consultancyDetails',
            'history.performer',
            'assignments.assignedBy', 'assignments.assignedTo',
            'comments.user',
            'documents.uploader',
        ]);

        $assignableUsers = User::whereIn('role', ['admin', 'staff'])
            ->where('status', 'active')
            ->where('id', '!=', auth()->id())
            ->orderBy('name')
            ->get();

        return view('payment-requests.show', compact('paymentRequest', 'assignableUsers'));
    }

    public function edit(PaymentRequest $paymentRequest): View
    {
        $this->authorize('update', $paymentRequest);

        return view('payment-requests.edit', [
            'paymentRequest' => $paymentRequest->load('consultancyDetails'),
            'type' => $paymentRequest->payment_type,
            'currencies' => config('payment_requests.supported_currencies'),
        ]);
    }

    public function update(UpdatePaymentRequestRequest $request, PaymentRequest $paymentRequest): RedirectResponse
    {
        $paymentRequest = $this->workflow->update($paymentRequest, $request->user(), $request->validated());

        if ($request->input('form_action') === 'submit') {
            return $this->submitAfterSave($request, $paymentRequest);
        }

        return redirect()->route('payment-requests.show', $paymentRequest)
            ->with('success', 'Changes saved.');
    }

    public function submit(Request $request, PaymentRequest $paymentRequest): RedirectResponse
    {
        $this->authorize('submit', $paymentRequest);

        return $this->transition(
            fn () => $this->workflow->submit($paymentRequest, $request->user()),
            fn (PaymentRequest $pr) => "Request {$pr->reference_number} submitted."
        );
    }

    public function assign(AssignPaymentRequestRequest $request, PaymentRequest $paymentRequest): RedirectResponse
    {
        $assignTo = User::findOrFail($request->validated('assigned_to'));

        return $this->transition(
            fn () => $this->workflow->assign($paymentRequest, $request->user(), $assignTo, $request->validated('comment')),
            fn () => "Request assigned to {$assignTo->name}."
        );
    }

    public function review(Request $request, PaymentRequest $paymentRequest): RedirectResponse
    {
        $this->authorize('review', $paymentRequest);

        return $this->transition(
            fn () => $this->workflow->review($paymentRequest, $request->user(), $request->input('comment')),
            fn () => 'Request marked as under review.'
        );
    }

    public function returnForCorrection(ReturnPaymentRequestRequest $request, PaymentRequest $paymentRequest): RedirectResponse
    {
        return $this->transition(
            fn () => $this->workflow->returnForCorrection($paymentRequest, $request->user(), $request->validated('reason')),
            fn () => 'Request returned for correction.'
        );
    }

    public function approve(ApprovePaymentRequestRequest $request, PaymentRequest $paymentRequest): RedirectResponse
    {
        return $this->transition(
            fn () => $this->workflow->approve($paymentRequest, $request->user(), $request->validated('comment')),
            fn () => 'Request approved.'
        );
    }

    public function reject(RejectPaymentRequestRequest $request, PaymentRequest $paymentRequest): RedirectResponse
    {
        return $this->transition(
            fn () => $this->workflow->reject($paymentRequest, $request->user(), $request->validated('reason')),
            fn () => 'Request rejected.'
        );
    }

    public function readyForPayment(Request $request, PaymentRequest $paymentRequest): RedirectResponse
    {
        $this->authorize('markReadyForPayment', $paymentRequest);

        return $this->transition(
            fn () => $this->workflow->markReadyForPayment($paymentRequest, $request->user()),
            fn () => 'Request marked ready for payment.'
        );
    }

    public function markPaid(MarkPaidRequest $request, PaymentRequest $paymentRequest): RedirectResponse
    {
        return $this->transition(
            fn () => $this->workflow->markPaid($paymentRequest, $request->user(), $request->validated()),
            fn (PaymentRequest $pr) => "Payment recorded - {$pr->reference_number} is now Paid."
        );
    }

    public function cancel(Request $request, PaymentRequest $paymentRequest): RedirectResponse
    {
        $this->authorize('cancel', $paymentRequest);

        return $this->transition(
            fn () => $this->workflow->cancel($paymentRequest, $request->user(), $request->input('reason')),
            fn () => 'Request cancelled.'
        );
    }

    /**
     * Comments are internal discussion, kept separate from
     * payment_request_history (which records workflow actions only).
     */
    public function addComment(CommentPaymentRequestRequest $request, PaymentRequest $paymentRequest): RedirectResponse
    {
        $paymentRequest->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        $this->notifyInterestedParties(
            $paymentRequest,
            $request->user(),
            'commented',
            "{$request->user()->name} commented on {$paymentRequest->displayReference()}."
        );

        return redirect()->route('payment-requests.show', $paymentRequest)
            ->with('success', 'Comment added.')
            ->withFragment('comments');
    }

    public function uploadDocument(UploadPaymentRequestDocumentRequest $request, PaymentRequest $paymentRequest): RedirectResponse
    {
        $file = $request->file('file');
        $disk = config('payment_requests.documents.disk');

        $path = $file->store('payment-request-documents/'.$paymentRequest->id, $disk);

        $document = $paymentRequest->documents()->create([
            'document_type' => $request->validated('document_type'),
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'mime_type' => $file->getClientMimeType() ?: $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'uploaded_by' => $request->user()->id,
        ]);

        $this->notifyInterestedParties(
            $paymentRequest,
            $request->user(),
            'document_uploaded',
            "{$request->user()->name} uploaded a document ({$document->typeLabel()}) to {$paymentRequest->displayReference()}."
        );

        return redirect()->route('payment-requests.show', $paymentRequest)
            ->with('success', 'Document uploaded.')
            ->withFragment('documents');
    }

    /**
     * The only way a document is ever reached - stored_path is never
     * exposed as a direct/public URL, and this route re-checks the same
     * 'view' policy as the detail page itself before streaming anything.
     */
    public function downloadDocument(PaymentRequest $paymentRequest, PaymentRequestDocument $document): StreamedResponse
    {
        $this->authorize('view', $paymentRequest);

        abort_unless($document->payment_request_id === $paymentRequest->id, 404);

        $disk = config('payment_requests.documents.disk');

        return Storage::disk($disk)->download($document->stored_path, $document->original_filename);
    }

    /**
     * Printable A4 voucher - a standalone document (its own <html>, no
     * admin navbar/footer) so it prints cleanly. Which paper form it
     * reproduces is decided entirely by PaymentType::printView().
     */
    public function print(PaymentRequest $paymentRequest): View
    {
        $this->authorize('print', $paymentRequest);

        $paymentRequest->load(['creator', 'currentAssignee', 'approver', 'consultancyDetails']);

        return view($paymentRequest->payment_type->printView(), compact('paymentRequest'));
    }

    // ── Internals ──────────────────────────────────────────────────

    private function submitAfterSave(Request $request, PaymentRequest $paymentRequest): RedirectResponse
    {
        $this->authorize('submit', $paymentRequest);

        try {
            $paymentRequest = $this->workflow->submit($paymentRequest, $request->user());
        } catch (DomainException $e) {
            return redirect()->route('payment-requests.show', $paymentRequest)->with('error', $e->getMessage());
        }

        return redirect()->route('payment-requests.show', $paymentRequest)
            ->with('success', "Request {$paymentRequest->reference_number} submitted.");
    }

    /**
     * Every transition action shares the same shape: run the workflow
     * call, catch an illegal-transition DomainException as a flash
     * error instead of a 500, otherwise flash success and go back to
     * the detail page.
     */
    private function transition(callable $action, callable $successMessage): RedirectResponse
    {
        try {
            $paymentRequest = $action();
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('payment-requests.show', $paymentRequest)
            ->with('success', $successMessage($paymentRequest));
    }

    /**
     * Comments and document uploads aren't workflow transitions (they
     * don't go through PaymentRequestWorkflowService), but people still
     * need to know about them - notifies the creator and the current
     * assignee, excluding whoever just performed the action, so nobody
     * misses activity on a request they're following.
     */
    private function notifyInterestedParties(PaymentRequest $paymentRequest, User $actor, string $event, string $message): void
    {
        $recipients = collect([$paymentRequest->creator, $paymentRequest->currentAssignee])
            ->filter()
            ->unique('id')
            ->reject(fn (User $u) => $u->id === $actor->id);

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new PaymentRequestUpdated($paymentRequest, $event, $message));
    }
}
