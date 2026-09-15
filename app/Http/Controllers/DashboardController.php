<?php

namespace App\Http\Controllers;

use App\Enums\PaymentRequestStatus;
use App\Models\PaymentRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Replaces Jetstream's stock dashboard.blade.php with a real
 * role-appropriate landing page for the Payment Request & Voucher
 * workflow: quick stat counts (via the same .web-top-card component
 * used on every other listing page) plus a short "what needs your
 * attention" list, so a user's very first screen already answers
 * "what's outstanding" without a click.
 */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            $stats = [
                'total' => PaymentRequest::query()->count(),
                'awaiting_action' => PaymentRequest::query()
                    ->whereIn('status', [
                        PaymentRequestStatus::Submitted,
                        PaymentRequestStatus::Assigned,
                        PaymentRequestStatus::UnderReview,
                    ])->count(),
                'ready_for_payment' => PaymentRequest::query()
                    ->where('status', PaymentRequestStatus::ReadyForPayment)->count(),
                'paid_this_month' => PaymentRequest::query()
                    ->where('status', PaymentRequestStatus::Paid)
                    ->whereMonth('paid_at', now()->month)
                    ->whereYear('paid_at', now()->year)
                    ->count(),
            ];

            $recent = PaymentRequest::query()
                ->with(['creator', 'currentAssignee'])
                ->latest('updated_at')
                ->take(8)
                ->get();
        } else {
            $stats = [
                'my_requests' => PaymentRequest::query()->createdBy($user->id)->count(),
                'my_drafts' => PaymentRequest::query()->createdBy($user->id)->status(PaymentRequestStatus::Draft)->count(),
                'my_tasks' => PaymentRequest::query()->awaitingActionFrom($user->id)->count(),
                'my_paid' => PaymentRequest::query()->createdBy($user->id)->status(PaymentRequestStatus::Paid)->count(),
            ];

            $recent = PaymentRequest::query()
                ->where(function ($q) use ($user) {
                    $q->where('created_by', $user->id)->orWhere('current_assignee_id', $user->id);
                })
                ->with(['creator', 'currentAssignee'])
                ->latest('updated_at')
                ->take(8)
                ->get();
        }

        return view('dashboard', [
            'isAdmin' => $user->isAdmin(),
            'stats' => $stats,
            'recent' => $recent,
        ]);
    }
}
