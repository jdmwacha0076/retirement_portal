<?php

namespace App\Http\Controllers;

use App\Http\Requests\BudgetSettings\ActivityTypeRequest;
use App\Models\ActivityType;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Admin-only master data CRUD. No destroy route is ever exposed - a
 * type already used by an activity is deactivated (is_active), never
 * removed, so historical activities never lose their type label.
 */
class ActivityTypeController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', ActivityType::class);

        $activityTypes = ActivityType::withCount('activities')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('budget-settings.activity-types.index', compact('activityTypes'));
    }

    public function store(ActivityTypeRequest $request): RedirectResponse
    {
        ActivityType::create($request->validated() + ['is_active' => true]);

        return back()->with('success', 'Activity type added.');
    }

    public function update(ActivityTypeRequest $request, ActivityType $activityType): RedirectResponse
    {
        $activityType->update($request->validated());

        return back()->with('success', 'Activity type updated.');
    }

    public function toggleActive(ActivityType $activityType): RedirectResponse
    {
        $this->authorize('toggleActive', $activityType);

        $activityType->update(['is_active' => ! $activityType->is_active]);

        return back()->with('success', $activityType->is_active
            ? 'Activity type activated.'
            : 'Activity type deactivated. It stays visible on historical activities but can no longer be picked for new ones.');
    }
}
