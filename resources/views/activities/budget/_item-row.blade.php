{{--
    Renders one <tr> of the budget-item builder table. Used twice from
    edit.blade.php: once per existing/old-input row inside a @foreach
    (real $index), and once more with $index = '__INDEX__' and $row =
    null inside the <template> the "Add Line" button clones - keeping
    the exact same markup in both places is what lets one delegated JS
    listener handle every row, new or existing, identically.

    Expects: $index (int|string), $row (array|null), $categories,
    $componentsByCategory, $paymentModes.
--}}
@php
    $categoryId = old("items.$index.budget_category_id", $row['budget_category_id'] ?? null);
    $componentId = old("items.$index.budget_component_id", $row['budget_component_id'] ?? null);
    $paymentMode = old("items.$index.payment_mode", $row['payment_mode'] ?? 'cash');
    $itemCode = $row['item_code'] ?? null;
@endphp
<tr class="budget-item-row" data-row>
    <td class="budget-item-code">
        <span class="budget-item-code-badge">{{ $itemCode ?: '—' }}</span>
        <input type="hidden" name="items[{{ $index }}][id]" value="{{ $row['id'] ?? '' }}">
    </td>

    <td>
        <select name="items[{{ $index }}][budget_category_id]" class="form-select form-select-sm" data-role="category" required>
            <option value="">Select…</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((int) $categoryId === $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <span class="form-field-error d-block">{{ $errors->first("items.$index.budget_category_id") }}</span>
    </td>

    <td>
        <select name="items[{{ $index }}][budget_component_id]" class="form-select form-select-sm" data-role="component">
            <option value="">—</option>
            @foreach ($componentsByCategory as $catId => $components)
                @foreach ($components as $component)
                    <option value="{{ $component->id }}" data-category-id="{{ $catId }}"
                        data-default-mode="{{ $component->default_payment_mode?->value }}"
                        @selected((int) $componentId === $component->id)
                        @if((int) $categoryId !== (int) $catId) hidden @endif>
                        {{ $component->name }}
                    </option>
                @endforeach
            @endforeach
        </select>
    </td>

    <td>
        <input type="text" name="items[{{ $index }}][description]" value="{{ old("items.$index.description", $row['description'] ?? '') }}"
            class="form-control form-control-sm" maxlength="255" placeholder="e.g. Return airfare" required>
        <span class="form-field-error d-block">{{ $errors->first("items.$index.description") }}</span>
    </td>

    <td>
        <input type="text" name="items[{{ $index }}][unit]" value="{{ old("items.$index.unit", $row['unit'] ?? '') }}"
            class="form-control form-control-sm" maxlength="50" placeholder="e.g. trip">
    </td>

    <td class="budget-num-cell">
        <input type="number" name="items[{{ $index }}][qty]" value="{{ old("items.$index.qty", $row['qty'] ?? 1) }}"
            class="form-control form-control-sm" data-role="qty" step="0.01" min="0.01" required>
    </td>

    <td class="budget-num-cell">
        <input type="number" name="items[{{ $index }}][frequency]" value="{{ old("items.$index.frequency", $row['frequency'] ?? 1) }}"
            class="form-control form-control-sm" data-role="frequency" step="1" min="1" required>
    </td>

    <td class="budget-num-cell">
        <input type="number" name="items[{{ $index }}][unit_cost]" value="{{ old("items.$index.unit_cost", $row['unit_cost'] ?? '') }}"
            class="form-control form-control-sm" data-role="unit-cost" step="0.01" min="0" required>
        <span class="form-field-error d-block">{{ $errors->first("items.$index.unit_cost") }}</span>
    </td>

    <td class="budget-num-cell">
        <input type="text" class="form-control form-control-sm budget-total-display" data-role="total" value="0.00" disabled tabindex="-1">
    </td>

    <td>
        <select name="items[{{ $index }}][payment_mode]" class="form-select form-select-sm" data-role="payment-mode">
            @foreach ($paymentModes as $value => $label)
                <option value="{{ $value }}" @selected($paymentMode === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </td>

    <td class="budget-num-cell">
        <input type="number" name="items[{{ $index }}][cash_amount]" value="{{ old("items.$index.cash_amount", $row['cash_amount'] ?? 0) }}"
            class="form-control form-control-sm" data-role="cash-amount" step="0.01" min="0">
        <span class="form-field-error d-block">{{ $errors->first("items.$index.cash_amount") }}</span>
    </td>

    <td class="budget-num-cell">
        <input type="number" name="items[{{ $index }}][invoice_amount]" value="{{ old("items.$index.invoice_amount", $row['invoice_amount'] ?? 0) }}"
            class="form-control form-control-sm" data-role="invoice-amount" step="0.01" min="0">
    </td>

    <td>
        <input type="text" name="items[{{ $index }}][notes]" value="{{ old("items.$index.notes", $row['notes'] ?? '') }}"
            class="form-control form-control-sm" maxlength="1000" placeholder="Optional">
    </td>

    <td class="text-end">
        <button type="button" class="portal-action-btn budget-remove-row" data-role="remove" title="Remove line">
            <i class="bi bi-trash3"></i>
        </button>
    </td>
</tr>
