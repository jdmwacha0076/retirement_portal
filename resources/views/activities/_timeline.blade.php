{{--
    Renders activity_history entries as a chronological timeline. $activity
    ->history() already orders newest-first. Nothing on this page can
    create, edit, or delete a history row - it's written only by
    ActivityService, which is what keeps this an actual audit trail rather
    than just a log (mirrors payment-requests._timeline).
--}}
@php
    $actionMeta = [
        'registered' => ['icon' => 'bi-file-earmark-plus-fill', 'label' => 'Registered'],
        'updated' => ['icon' => 'bi-pencil-square', 'label' => 'Details updated'],
        'activated' => ['icon' => 'bi-play-circle-fill', 'label' => 'Activated'],
        'cancelled' => ['icon' => 'bi-slash-circle-fill', 'label' => 'Cancelled'],
    ];
@endphp

<div class="form-section-card">
    <div class="form-section-header">
        <h4>Activity Log</h4>
        <p>A complete, immutable record of every action taken on this activity.</p>
    </div>

    @if ($activity->history->isEmpty())
        <p class="text-muted mb-0">No history recorded yet.</p>
    @else
        <ul class="list-group list-group-flush">
            @foreach ($activity->history as $entry)
                @php
                    $meta = $actionMeta[$entry->action] ?? ['icon' => 'bi-clock-history', 'label' => ucfirst(str_replace('_', ' ', $entry->action))];
                    $fromStatus = $entry->from_status ? \App\Enums\ActivityStatus::from($entry->from_status) : null;
                    $toStatus = $entry->to_status ? \App\Enums\ActivityStatus::from($entry->to_status) : null;
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

                            <p class="mb-0 small text-muted">by {{ $entry->performer->name ?? 'System' }}</p>

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
