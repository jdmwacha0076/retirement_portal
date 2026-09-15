{{--
    Renders payment_request_history entries as a chronological timeline.
    $history is already ordered newest-first (PaymentRequest::history()
    applies ->latest('created_at')) - that's the natural reading order for
    an audit trail ("what happened most recently").

    Every row here comes straight from PaymentRequestWorkflowService -
    nothing on this page can create, edit, or delete a history row, which
    is what keeps this an actual audit trail rather than just a log.
--}}
@php
    $actionMeta = [
        'created' => ['icon' => 'bi-file-earmark-plus-fill', 'label' => 'Created as draft'],
        'edited' => ['icon' => 'bi-pencil-square', 'label' => 'Edited'],
        'submitted' => ['icon' => 'bi-send-check-fill', 'label' => 'Submitted'],
        'resubmitted' => ['icon' => 'bi-arrow-repeat', 'label' => 'Resubmitted'],
        'assigned' => ['icon' => 'bi-person-arms-up', 'label' => 'Assigned'],
        'reviewed' => ['icon' => 'bi-search', 'label' => 'Marked under review'],
        'returned' => ['icon' => 'bi-reply-fill', 'label' => 'Returned for correction'],
        'approved' => ['icon' => 'bi-check-circle-fill', 'label' => 'Approved'],
        'rejected' => ['icon' => 'bi-x-circle-fill', 'label' => 'Rejected'],
        'ready_for_payment' => ['icon' => 'bi-hourglass-split', 'label' => 'Marked ready for payment'],
        'paid' => ['icon' => 'bi-cash-stack', 'label' => 'Payment recorded'],
        'cancelled' => ['icon' => 'bi-slash-circle-fill', 'label' => 'Cancelled'],
    ];
@endphp

@if ($history->isEmpty())
    <p class="text-muted mb-0">No history recorded yet.</p>
@else
    <ul class="list-group list-group-flush">
        @foreach ($history as $entry)
            @php
                $meta = $actionMeta[$entry->action] ?? ['icon' => 'bi-clock-history', 'label' => ucfirst(str_replace('_', ' ', $entry->action))];
            @endphp

            <li class="list-group-item px-0">
                <div class="d-flex align-items-start gap-3">
                    <div class="portal-entity-icon flex-shrink-0">
                        <i class="bi {{ $meta['icon'] }}"></i>
                    </div>

                    <div class="flex-grow-1">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <strong>{{ $meta['label'] }}</strong>

                            @if ($entry->from_status && $entry->to_status && $entry->from_status !== $entry->to_status)
                                <span class="status-badge {{ $entry->to_status->badgeClass() }}">
                                    <i class="bi {{ $entry->to_status->icon() }}"></i>
                                    {{ $entry->from_status->label() }} &rarr; {{ $entry->to_status->label() }}
                                </span>
                            @endif

                            <time class="text-muted small">{{ $entry->created_at->format('d M Y, g:ia') }}</time>
                        </div>

                        <p class="mb-0 small text-muted">
                            by {{ $entry->performer->name ?? 'System' }}

                            @if ($entry->action === 'assigned' && ($entry->metadata['assigned_to_name'] ?? null))
                                &rarr; {{ $entry->metadata['assigned_to_name'] }}
                            @endif
                        </p>

                        @if ($entry->comment)
                            <p class="mb-0 mt-1">{{ $entry->comment }}</p>
                        @endif
                    </div>
                </div>
            </li>
        @endforeach
    </ul>
@endif
