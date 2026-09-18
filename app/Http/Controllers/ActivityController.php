<?php

namespace App\Http\Controllers;

use App\Enums\ActivityStatus;
use App\Http\Requests\Activities\CancelActivityRequest;
use App\Http\Requests\Activities\StoreActivityRequest;
use App\Http\Requests\Activities\UpdateActivityRequest;
use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\User;
use App\Services\ActivityService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function __construct(private readonly ActivityService $activities)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Activity::class);

        $user = $request->user();
        $mine = $request->boolean('mine');

        $query = Activity::with([
            'activityType',
            'creator',
            'coordinator',
            // For currentlyAssignedTo() on each row without N+1s.
            'currentBudget.currentAssignee',
            'currentBudget.creator',
            'currentBudget.currentRetirement.currentAssignee',
            'currentBudget.currentRetirement.creator',
        ])->latest('created_at');

        // Staff only ever see activities they created, coordinate, or are
        // (or were ever) assigned on via the current budget/retirement -
        // Activity::scopeVisibleTo() has the full three-tier rule. Admin
        // sees everything by default, with an optional "Mine" filter via
        // ?mine=1 for their own.
        if (! $user->isAdmin() || $mine) {
            $query->visibleTo($user);
        }

        if ($search = trim((string) $request->string('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('accounting_code', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->value()) {
            $query->where('status', $status);
        }

        $activities = $query->paginate(15)->withQueryString();

        $statuses = ActivityStatus::options();

        return view('activities.index', compact('activities', 'statuses', 'mine'));
    }

    public function create(): View
    {
        $this->authorize('create', Activity::class);

        $activityTypes = ActivityType::active()->orderBy('sort_order')->orderBy('name')->get();
        $coordinators = User::where('status', 'active')->orderBy('name')->get();
        $currencies = config('activity_budgets.supported_currencies');
        $defaultCurrency = config('activity_budgets.default_currency');

        return view('activities.create', compact('activityTypes', 'coordinators', 'currencies', 'defaultCurrency'));
    }

    public function store(StoreActivityRequest $request): RedirectResponse
    {
        $activity = $this->activities->register($request->validated(), $request->user());

        return redirect()->route('activities.show', $activity)
            ->with('success', 'Activity registered — reference '.$activity->reference.'.');
    }

    public function show(Activity $activity): View
    {
        $this->authorize('view', $activity);

        $activity->load([
            'activityType',
            'creator',
            'coordinator',
            'completedBy',
            'cancelledBy',
            'history.performer',
            // For currentlyAssignedTo() in the sidebar "People" card.
            'currentBudget.currentAssignee',
            'currentBudget.creator',
            'currentBudget.currentRetirement.currentAssignee',
            'currentBudget.currentRetirement.creator',
        ]);

        return view('activities.show', compact('activity'));
    }

    public function edit(Activity $activity): View
    {
        $this->authorize('update', $activity);

        $activityTypes = ActivityType::query()
            ->where('is_active', true)
            ->orWhere('id', $activity->activity_type_id)
            ->orderBy('sort_order')->orderBy('name')->get();
        $coordinators = User::where('status', 'active')->orderBy('name')->get();
        $currencies = config('activity_budgets.supported_currencies');

        return view('activities.edit', compact('activity', 'activityTypes', 'coordinators', 'currencies'));
    }

    public function update(UpdateActivityRequest $request, Activity $activity): RedirectResponse
    {
        $this->activities->update($activity, $request->validated(), $request->user());

        return redirect()->route('activities.show', $activity)->with('success', 'Activity updated.');
    }

    public function cancel(CancelActivityRequest $request, Activity $activity): RedirectResponse
    {
        try {
            $this->activities->cancel($activity, $request->user(), $request->validated()['cancellation_reason']);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('activities.show', $activity)->with('success', 'Activity cancelled.');
    }
}
