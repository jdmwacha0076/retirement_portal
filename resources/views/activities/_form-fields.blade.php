@php
    // $activity is only present on the edit form - null on create, so
    // old(...) falls back to the model's current value on edit and to a
    // blank/default on create, in one expression per field.
    $a = $activity ?? null;
@endphp

<div class="form-section-card">
    <div class="form-section-header">
        <div>
            <span class="portal-section-kicker">
                <i class="bi bi-info-circle-fill"></i>
                Overview
            </span>
            <h4>Activity Details</h4>
            <p>The core information used across the budget and retirement for this activity.</p>
        </div>
    </div>

    <div class="form-grid">

        <div class="form-field">
            <label for="activity_type_id" class="form-label">Activity Type <span>*</span></label>
            <div class="form-input-wrap">
                <i class="bi bi-tags-fill"></i>
                <select id="activity_type_id" name="activity_type_id" class="form-control-custom @error('activity_type_id') is-invalid @enderror" required>
                    <option value="">Select a type</option>
                    @foreach ($activityTypes as $type)
                        <option value="{{ $type->id }}" @selected((int) old('activity_type_id', $a?->activity_type_id) === $type->id)>
                            {{ $type->name }}{{ $type->is_active ? '' : ' (inactive)' }}
                        </option>
                    @endforeach
                </select>
            </div>
            @error('activity_type_id')<span class="form-server-error">{{ $message }}</span>@enderror
        </div>

        <div class="form-field">
            <label for="accounting_code" class="form-label">Budget / Accounting Code <small class="text-muted">(optional)</small></label>
            <div class="form-input-wrap">
                <i class="bi bi-upc-scan"></i>
                <input type="text" id="accounting_code" name="accounting_code"
                    value="{{ old('accounting_code', $a?->accounting_code) }}"
                    class="form-control-custom @error('accounting_code') is-invalid @enderror"
                    maxlength="100" placeholder="e.g. LGH27/26">
            </div>
            <span class="form-input-help">Used for the printed budget's item numbering (e.g. LGH27/26-A1). Leave blank to use the system reference instead.</span>
            @error('accounting_code')<span class="form-server-error">{{ $message }}</span>@enderror
        </div>

        <div class="form-field form-grid-full">
            <label for="title" class="form-label">Activity Title <span>*</span></label>
            <div class="form-input-wrap">
                <i class="bi bi-card-heading"></i>
                <input type="text" id="title" name="title"
                    value="{{ old('title', $a?->title) }}"
                    class="form-control-custom @error('title') is-invalid @enderror"
                    maxlength="255" required placeholder="e.g. NMCN and UNICEF Training Support">
            </div>
            @error('title')<span class="form-server-error">{{ $message }}</span>@enderror
        </div>

        <div class="form-field form-grid-full">
            <label for="purpose" class="form-label">Purpose / Objective <span>*</span></label>
            <div class="form-textarea-wrap">
                <textarea id="purpose" name="purpose" rows="2"
                    class="form-control-custom @error('purpose') is-invalid @enderror"
                    maxlength="2000" required>{{ old('purpose', $a?->purpose) }}</textarea>
            </div>
            @error('purpose')<span class="form-server-error">{{ $message }}</span>@enderror
        </div>

        <div class="form-field">
            <label for="program" class="form-label">Project / Program <small class="text-muted">(optional)</small></label>
            <div class="form-input-wrap">
                <i class="bi bi-diagram-3-fill"></i>
                <input type="text" id="program" name="program"
                    value="{{ old('program', $a?->program) }}"
                    class="form-control-custom @error('program') is-invalid @enderror" maxlength="255">
            </div>
            @error('program')<span class="form-server-error">{{ $message }}</span>@enderror
        </div>

        <div class="form-field">
            <label for="coordinator_id" class="form-label">Activity Coordinator <small class="text-muted">(optional)</small></label>
            <div class="form-input-wrap">
                <i class="bi bi-person-badge-fill"></i>
                <select id="coordinator_id" name="coordinator_id" class="form-control-custom @error('coordinator_id') is-invalid @enderror">
                    <option value="">—</option>
                    @foreach ($coordinators as $coordinator)
                        <option value="{{ $coordinator->id }}" @selected((int) old('coordinator_id', $a?->coordinator_id) === $coordinator->id)>{{ $coordinator->name }}</option>
                    @endforeach
                </select>
            </div>
            @error('coordinator_id')<span class="form-server-error">{{ $message }}</span>@enderror
        </div>

        <div class="form-field">
            <label for="location" class="form-label">Location <small class="text-muted">(optional)</small></label>
            <div class="form-input-wrap">
                <i class="bi bi-geo-alt-fill"></i>
                <input type="text" id="location" name="location"
                    value="{{ old('location', $a?->location) }}"
                    class="form-control-custom @error('location') is-invalid @enderror" maxlength="255">
            </div>
            @error('location')<span class="form-server-error">{{ $message }}</span>@enderror
        </div>

        <div class="form-field">
            <label for="country" class="form-label">Country <small class="text-muted">(optional)</small></label>
            <div class="form-input-wrap">
                <i class="bi bi-flag-fill"></i>
                <input type="text" id="country" name="country"
                    value="{{ old('country', $a?->country) }}"
                    class="form-control-custom @error('country') is-invalid @enderror" maxlength="255">
            </div>
            @error('country')<span class="form-server-error">{{ $message }}</span>@enderror
        </div>

        <div class="form-field">
            <label for="venue" class="form-label">Venue <small class="text-muted">(optional)</small></label>
            <div class="form-input-wrap">
                <i class="bi bi-building"></i>
                <input type="text" id="venue" name="venue"
                    value="{{ old('venue', $a?->venue) }}"
                    class="form-control-custom @error('venue') is-invalid @enderror" maxlength="255">
            </div>
            @error('venue')<span class="form-server-error">{{ $message }}</span>@enderror
        </div>

        <div class="form-field">
            <label for="participant_count" class="form-label">Number of Participants <small class="text-muted">(optional)</small></label>
            <div class="form-input-wrap">
                <i class="bi bi-people-fill"></i>
                <input type="number" id="participant_count" name="participant_count" min="1"
                    value="{{ old('participant_count', $a?->participant_count) }}"
                    class="form-control-custom @error('participant_count') is-invalid @enderror">
            </div>
            @error('participant_count')<span class="form-server-error">{{ $message }}</span>@enderror
        </div>

        <div class="form-field">
            <label for="start_date" class="form-label">Start Date <span>*</span></label>
            <div class="form-input-wrap">
                <i class="bi bi-calendar-event-fill"></i>
                <input type="date" id="start_date" name="start_date"
                    value="{{ old('start_date', optional($a?->start_date)->format('Y-m-d')) }}"
                    class="form-control-custom @error('start_date') is-invalid @enderror" required>
            </div>
            @error('start_date')<span class="form-server-error">{{ $message }}</span>@enderror
        </div>

        <div class="form-field">
            <label for="end_date" class="form-label">End Date <span>*</span></label>
            <div class="form-input-wrap">
                <i class="bi bi-calendar-check-fill"></i>
                <input type="date" id="end_date" name="end_date"
                    value="{{ old('end_date', optional($a?->end_date)->format('Y-m-d')) }}"
                    class="form-control-custom @error('end_date') is-invalid @enderror" required>
            </div>
            @error('end_date')<span class="form-server-error">{{ $message }}</span>@enderror
        </div>

        <div class="form-field">
            <label for="currency" class="form-label">Budget Currency <span>*</span></label>
            <div class="form-input-wrap">
                <i class="bi bi-cash"></i>
                <select id="currency" name="currency" class="form-control-custom @error('currency') is-invalid @enderror" required>
                    @foreach ($currencies as $currency)
                        <option value="{{ $currency }}" @selected(old('currency', $a?->currency ?? $defaultCurrency ?? $currencies[0]) === $currency)>{{ $currency }}</option>
                    @endforeach
                </select>
            </div>
            @error('currency')<span class="form-server-error">{{ $message }}</span>@enderror
        </div>

        <div class="form-field form-grid-full">
            <label for="description" class="form-label">Description / Notes <small class="text-muted">(optional)</small></label>
            <div class="form-textarea-wrap">
                <textarea id="description" name="description" rows="3"
                    class="form-control-custom @error('description') is-invalid @enderror"
                    maxlength="4000">{{ old('description', $a?->description) }}</textarea>
            </div>
            @error('description')<span class="form-server-error">{{ $message }}</span>@enderror
        </div>

    </div>
</div>
