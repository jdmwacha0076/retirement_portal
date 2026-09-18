{{--
    Renders an ActivityBudget's activity_history entries as a
    chronological timeline - same shared polymorphic table Activity's own
    _timeline uses, just a different $actionMeta vocabulary for the
    budget workflow's actions. Mirrors payment-requests._timeline in
    structure/classes.
--}}
@php
    $actionMeta = [
        'started' => ['icon' => 'bi-file-earmark-plus-fill', 'label' => 'Budget started'],
        'items_updated' => ['icon' => 'bi-pencil-square', 'label' => 'Items saved'],
        'submitted' => ['icon' => 'bi-send-check-fill', 'label' => 'Submitted'],
        'resubmitted' => ['icon' => 'bi-arrow-repeat', 'label' => 'Resubmitted'],
        'assigned' => ['icon' => 'bi-person-arms-up', 'label' => 'Assigned'],
        'reviewed' => ['icon' => 'bi-search', 'label' => 'Marked under review'],
        'returned' => ['icon' => 'bi-reply-fill', 'label' => 'Returned for correction'],
        'approved' => ['icon' => 'bi-check-circle-fill', 'label' => 'Approved'],
        'rejected' => ['icon' => 'bi-x-circle-fill', 'label' => 'Rejected'],
        'cancelled' => ['icon' => 'bi-slash-circle-fill', 'label' => 'Cancelled'],
    ];
@endphp

<div class="form-section-card">
    <div class="form-section-header">
        <h4>History &amp; Audit Trail</h4>
        <p>A complete, immutable record of every action taken on this budget.</p>
    </div>

    @if ($budget->history->isEmpty())
        <p class="text-muted mb-0">No history recorded yet.</p>
    @else
        <ul class="list-group list-group-flush">
            @foreach ($budget->history as $entry)
                @php
                    $meta = $actionMeta[$entry->action] ?? ['icon' => 'bi-clock-history', 'label' => ucfirst(str_replace('_', ' ', $entry->action))];
                    $fromStatus = $entry->from_status ? \App\Enums\ActivityBudgetStatus::from($entry->from_status) : null;
                    $toStatus = $entry->to_status ? \App\Enums\ActivityBudgetStatus::from($entry->to_status) : null;
                @endphp

                <li class="list-group-item px-0">
                    <div class="d-flex align-items-start gap-3">
                        <div class="portal-entity-icon flex-shrink-0">
                            <i class="bi {{ $meta['icon'] }}"></i>
                        </div>

                        <div class="flex-grow-1">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <strong>{{ $meta['label'] }}</strong>

                                @if ($fromStatus && $toStatus && $fromStatus !== $toStatus)
                                    <span class="status-badge {{ $toStatus->badgeClass() }}">
                                        <i class="bi {{ $toStatus->icon() }}"></i>
                                        {{ $fromStatus->label() }} &rarr; {{ $toStatus->label() }}
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
</div>
