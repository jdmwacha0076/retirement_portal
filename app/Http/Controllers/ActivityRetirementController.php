<?php

namespace App\Http\Controllers;

use App\Http\Requests\Activities\SaveActivityRetirementItemsRequest;
use App\Http\Requests\Activities\UploadRetirementDocumentRequest;
use App\Models\Activity;
use App\Models\ActivityRetirement;
use App\Models\RetirementDocument;
use App\Services\ActivityRetirementService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The Activity Retirement builder: accounting for how an approved
 * budget's advance was actually spent, one line per approved budget
 * item, plus receipt uploads (general and per-line). Same one-GET-
 * entry-point shape as ActivityBudgetController::edit() - renders a
 * "Start Retirement" prompt or the builder depending on whether one
 * already exists for the activity's current budget.
 *
 * Scoped to data entry only for now (start + save actuals + receipts) -
 * Submit/Assign/Review/Approve/Reject/Cancel workflow for the retirement
 * itself is a later phase, mirroring how ActivityBudgetController shipped
 * its builder (Phase 4) well before its own workflow (Phase 6).
 */
class ActivityRetirementController extends Controller
{
    public function __construct(private readonly ActivityRetirementService $retirements)
    {
    }

    public function edit(Activity $activity): View
    {
        $this->authorize('view', $activity);

        $budget = $activity->currentBudget;
        $retirement = $budget?->currentRetirement;

        if (! $retirement) {
            $canStart = $budget !== null && Gate::allows('create', [ActivityRetirement::class, $budget]);

            return view('activities.retirement.edit', [
                'activity' => $activity,
                'budget' => $budget,
                'retirement' => null,
                'canStart' => $canStart,
                'canEdit' => false,
                'canUpload' => false,
                'rows' => [],
                'generalDocuments' => collect(),
            ]);
        }

        $this->authorize('view', $retirement);

        $canEdit = Gate::allows('update', $retirement);
        $canUpload = Gate::allows('uploadDocument', $retirement);
        // A retirement already exists for this budget - never the
        // "Start" prompt, same convention as ActivityBudgetController.
        $canStart = false;

        $retirement->load(['history.performer']);

        // Unlike ActivityBudgetController's `old('items') ?? $freshQuery`
        // swap, this ALWAYS uses the fresh query - rows here are a fixed
        // 1:1 set with the approved budget's items (never added/removed
        // by the user, unlike budget lines), so there's no "brand new row
        // with no DB id yet" case to reconstruct from old() at the top
        // level. Each editable field still falls back to old() itself
        // inside _item-row.blade.php, which is what actually restores the
        // user's typed values after a validation error - swapping the
        // whole array here would instead DROP every read-only reference
        // field (item_code, description, approved_*), since those were
        // never part of the posted form data in the first place.
        $rows = $retirement->items()
            ->with(['activityBudgetItem.category', 'activityBudgetItem.component', 'documents'])
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'item_code' => $item->activityBudgetItem->item_code,
                'description' => $item->activityBudgetItem->description,
                'category' => $item->activityBudgetItem->category?->name,
                'component' => $item->activityBudgetItem->component?->name,
                'approved_qty' => $item->activityBudgetItem->qty,
                'approved_frequency' => $item->activityBudgetItem->frequency,
                'approved_unit_cost' => $item->activityBudgetItem->unit_cost,
                'approved_total' => $item->activityBudgetItem->total,
                'approved_payment_mode' => $item->activityBudgetItem->payment_mode->value,
                'actual_qty' => $item->actual_qty,
                'actual_frequency' => $item->actual_frequency,
                'actual_unit_cost' => $item->actual_unit_cost,
                'actual_payment_mode' => $item->actual_payment_mode?->value,
                'actual_cash_amount' => $item->actual_cash_amount,
                'actual_invoice_amount' => $item->actual_invoice_amount,
                'payment_mode_change_justification' => $item->payment_mode_change_justification,
                'variance_justification' => $item->variance_justification,
                'notes' => $item->notes,
                'documents' => $item->documents->map(fn ($doc) => [
                    'id' => $doc->id,
                    'label' => $doc->typeLabel(),
                    'filename' => $doc->original_filename,
                    'amount' => $doc->document_amount !== null ? (float) $doc->document_amount : null,
                    'download_url' => route('activities.retirement.documents.download', [$activity, $doc]),
                ])->values()->all(),
            ])->values()->all();

        $generalDocuments = $retirement->documents; // item_id null, per model scope

        return view('activities.retirement.edit', compact(
            'activity', 'budget', 'retirement', 'canStart', 'canEdit', 'canUpload', 'rows', 'generalDocuments'
        ));
    }

    public function store(Activity $activity): RedirectResponse
    {
        $budget = $activity->currentBudget;
        abort_if(! $budget, 404);

        $this->authorize('create', [ActivityRetirement::class, $budget]);

        try {
            $this->retirements->start($budget, request()->user());
        } catch (DomainException $e) {
            return redirect()->route('activities.retirement.edit', $activity)->with('error', $e->getMessage());
        }

        return redirect()->route('activities.retirement.edit', $activity)
            ->with('success', 'Retirement started — record actual expenses and upload receipts below, then save.');
    }

    public function update(SaveActivityRetirementItemsRequest $request, Activity $activity): RedirectResponse
    {
        $retirement = $activity->currentBudget?->currentRetirement;
        abort_if(! $retirement, 404);

        try {
            $this->retirements->saveItems($retirement, $request->validated('items'), $request->user());
        } catch (DomainException $e) {
            return redirect()->route('activities.retirement.edit', $activity)->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('activities.retirement.edit', $activity)->with('success', 'Retirement expenses saved.');
    }

    public function uploadDocument(UploadRetirementDocumentRequest $request, Activity $activity): RedirectResponse
    {
        $retirement = $activity->currentBudget?->currentRetirement;
        abort_if(! $retirement, 404);

        $file = $request->file('file');
        $disk = config('activity_budgets.documents.disk');

        $path = $file->store('retirement-documents/'.$retirement->id, $disk);

        $retirement->allDocuments()->create([
            'activity_retirement_item_id' => $request->validated('activity_retirement_item_id'),
            'document_type' => $request->validated('document_type'),
            'receipt_number' => $request->validated('receipt_number'),
            'receipt_date' => $request->validated('receipt_date'),
            'vendor' => $request->validated('vendor'),
            'document_amount' => $request->validated('document_amount'),
            'currency' => $request->validated('currency') ?: $retirement->activityBudget->currency,
            'description' => $request->validated('description'),
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'uploaded_by' => $request->user()->id,
        ]);

        return redirect()->route('activities.retirement.edit', $activity)
            ->with('success', 'Receipt uploaded.')
            ->withFragment('receipts');
    }

    /**
     * The only way a receipt is ever reached - stored_path is never
     * exposed as a direct/public URL, and this route re-checks the same
     * 'view' policy as the builder page itself before streaming anything.
     * Mirrors PaymentRequestController::downloadDocument().
     */
    public function downloadDocument(Activity $activity, RetirementDocument $document): StreamedResponse
    {
        $this->authorize('view', $activity);

        $retirement = $activity->currentBudget?->currentRetirement;

        abort_unless($retirement && $document->activity_retirement_id === $retirement->id, 404);

        $disk = config('activity_budgets.documents.disk');

        return Storage::disk($disk)->download($document->stored_path, $document->original_filename);
    }

    /**
     * A4-landscape, browser-print-to-PDF report - approved vs actual per
     * line, plus every receipt (general and per-line) in one supporting
     * table. Same convention as ActivityBudgetController::print() and,
     * further back, the Payment Request voucher pages: "Save as PDF" in
     * the browser's own print dialog, no server-side PDF library.
     */
    public function print(Activity $activity): View
    {
        $retirement = $activity->currentBudget?->currentRetirement;
        abort_if(! $retirement, 404);

        $this->authorize('print', $retirement);

        $budget = $activity->currentBudget;

        $retirement->load(['items.activityBudgetItem.category', 'items.activityBudgetItem.component', 'creator']);
        $documents = $retirement->allDocuments()->with('activityRetirementItem.activityBudgetItem')->get();

        return view('activities.retirement.print', compact('activity', 'budget', 'retirement', 'documents'));
    }

    /**
     * Plain CSV (not a real .xlsx) so it needs no new composer package -
     * opens directly in Excel/Sheets. Same UTF-8-BOM convention as
     * ActivityBudgetController::exportCsv(). Expense lines and receipts
     * are two stacked tables in the one file, separated by a blank row -
     * a real .xlsx would use two sheets, but CSV has only one.
     */
    public function exportCsv(Activity $activity): StreamedResponse
    {
        $retirement = $activity->currentBudget?->currentRetirement;
        abort_if(! $retirement, 404);

        $this->authorize('print', $retirement);

        $budget = $activity->currentBudget;

        $retirement->load(['items.activityBudgetItem.category', 'items.activityBudgetItem.component']);
        $documents = $retirement->allDocuments()->with('activityRetirementItem.activityBudgetItem')->get();

        $totalDues = (float) $retirement->unspent_advance_amount + (float) $retirement->reimbursement_due_amount;

        $filename = Str::slug($retirement->reference).'-retirement.csv';

        return response()->streamDownload(function () use ($activity, $budget, $retirement, $documents, $totalDues) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['Activity', $activity->reference]);
            fputcsv($out, ['Title', $activity->title]);
            fputcsv($out, ['Retirement', $retirement->reference]);
            fputcsv($out, ['Status', $retirement->status->label()]);
            fputcsv($out, ['Currency', $budget->currency]);
            fputcsv($out, ['Amount Provided', $retirement->total_advanced]);
            fputcsv($out, ['Amount Spent', $retirement->total_actual_overall]);
            fputcsv($out, ['Due to Praxis', $retirement->unspent_advance_amount]);
            fputcsv($out, ['Due to the Creator', $retirement->reimbursement_due_amount]);
            fputcsv($out, ['Total Dues', $totalDues]);
            fputcsv($out, []);

            fputcsv($out, [
                'Item Code', 'Description', 'Category', 'Component', 'Approved Qty', 'Approved Frequency', 'Approved Unit Cost', 'Approved Total', 'Approved Mode',
                'Actual Qty', 'Actual Frequency', 'Actual Unit Cost', 'Actual Total', 'Actual Mode', 'Cash', 'Invoice',
                'Variance Justification', 'Payment Mode Change Justification', 'Notes',
            ]);

            foreach ($retirement->items as $item) {
                $budgetItem = $item->activityBudgetItem;

                fputcsv($out, [
                    $budgetItem->item_code,
                    $budgetItem->description,
                    $budgetItem->category->name ?? '',
                    $budgetItem->component->name ?? '',
                    $budgetItem->qty,
                    $budgetItem->frequency,
                    $budgetItem->unit_cost,
                    $budgetItem->total,
                    $budgetItem->payment_mode->label(),
                    $item->actual_qty,
                    $item->actual_frequency,
                    $item->actual_unit_cost,
                    $item->actual_total,
                    $item->actual_payment_mode?->label(),
                    $item->actual_cash_amount,
                    $item->actual_invoice_amount,
                    $item->variance_justification,
                    $item->payment_mode_change_justification,
                    $item->notes,
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, ['', '', '', '', '', '', '', '', '', '', '', '', 'Totals', '', $retirement->total_actual_cash, $retirement->total_actual_invoice, '', '', '']);

            if ($documents->isNotEmpty()) {
                fputcsv($out, []);
                fputcsv($out, ['Receipts & Supporting Documents']);
                fputcsv($out, ['Line', 'Type', 'Vendor', 'Receipt #', 'Date', 'Amount', 'File']);

                foreach ($documents as $document) {
                    fputcsv($out, [
                        $document->activityRetirementItem?->activityBudgetItem?->item_code ?? 'General',
                        $document->typeLabel(),
                        $document->vendor,
                        $document->receipt_number,
                        $document->receipt_date?->format('Y-m-d'),
                        $document->document_amount,
                        $document->original_filename,
                    ]);
                }
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
