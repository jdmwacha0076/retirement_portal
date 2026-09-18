<?php

use App\Http\Controllers\ActivityBudgetController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ActivityRetirementController;
use App\Http\Controllers\ActivityTypeController;
use App\Http\Controllers\BudgetCategoryController;
use App\Http\Controllers\BudgetComponentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'role:admin,staff',
])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllRead'])->name('mark-all-read');
        Route::get('/{notification}/open', [NotificationController::class, 'open'])->name('open');
    });

    Route::prefix('payment-requests')->name('payment-requests.')->group(function () {
        Route::get('/', [PaymentRequestController::class, 'index'])->name('index');
        Route::get('/my-tasks', [PaymentRequestController::class, 'myTasks'])->name('my-tasks');
        Route::get('/create', [PaymentRequestController::class, 'typePicker'])->name('type-picker');
        Route::get('/create/{type}', [PaymentRequestController::class, 'create'])->name('create');
        Route::post('/create/{type}', [PaymentRequestController::class, 'store'])->name('store');

        Route::get('/{payment_request}', [PaymentRequestController::class, 'show'])->name('show');
        Route::get('/{payment_request}/edit', [PaymentRequestController::class, 'edit'])->name('edit');
        Route::put('/{payment_request}', [PaymentRequestController::class, 'update'])->name('update');

        Route::post('/{payment_request}/submit', [PaymentRequestController::class, 'submit'])->name('submit');
        Route::post('/{payment_request}/assign', [PaymentRequestController::class, 'assign'])->name('assign');
        Route::post('/{payment_request}/review', [PaymentRequestController::class, 'review'])->name('review');
        Route::post('/{payment_request}/return', [PaymentRequestController::class, 'returnForCorrection'])->name('return');
        Route::post('/{payment_request}/approve', [PaymentRequestController::class, 'approve'])->name('approve');
        Route::post('/{payment_request}/reject', [PaymentRequestController::class, 'reject'])->name('reject');
        Route::post('/{payment_request}/ready-for-payment', [PaymentRequestController::class, 'readyForPayment'])->name('ready-for-payment');
        Route::post('/{payment_request}/mark-paid', [PaymentRequestController::class, 'markPaid'])->name('mark-paid');
        Route::post('/{payment_request}/cancel', [PaymentRequestController::class, 'cancel'])->name('cancel');
        Route::get('/{payment_request}/print', [PaymentRequestController::class, 'print'])->name('print');

        Route::post('/{payment_request}/comments', [PaymentRequestController::class, 'addComment'])->name('comments.store');
        Route::post('/{payment_request}/documents', [PaymentRequestController::class, 'uploadDocument'])->name('documents.store');
        Route::get('/{payment_request}/documents/{document}/download', [PaymentRequestController::class, 'downloadDocument'])->name('documents.download');
    });

    // Activity Budget module - Activity registration (Phase 3). Both
    // roles may register/view their own activities, per the approved
    // authorization matrix.
    Route::prefix('activities')->name('activities.')->group(function () {
        Route::get('/', [ActivityController::class, 'index'])->name('index');
        Route::get('/create', [ActivityController::class, 'create'])->name('create');
        Route::post('/', [ActivityController::class, 'store'])->name('store');
        Route::get('/{activity}', [ActivityController::class, 'show'])->name('show');
        Route::get('/{activity}/edit', [ActivityController::class, 'edit'])->name('edit');
        Route::put('/{activity}', [ActivityController::class, 'update'])->name('update');
        Route::post('/{activity}/cancel', [ActivityController::class, 'cancel'])->name('cancel');

        // Activity Budget module - budget builder (Phase 4). One GET
        // entry point handles both the "Start Budget" prompt and the
        // line-item builder itself - see ActivityBudgetController::edit().
        Route::prefix('/{activity}/budget')->name('budget.')->group(function () {
            Route::get('/', [ActivityBudgetController::class, 'edit'])->name('edit');
            Route::post('/', [ActivityBudgetController::class, 'store'])->name('store');
            Route::put('/', [ActivityBudgetController::class, 'update'])->name('update');

            // Budget workflow - Submit/Assign/Review/Cancel (Phase 6)
            // plus Approve/Reject (Phase 7). Assign also covers "return
            // for correction" now - assigning the budget back to its own
            // creator IS a return, so there's no separate /return route
            // anymore (see ActivityBudgetWorkflowService::assign()).
            Route::post('/submit', [ActivityBudgetController::class, 'submit'])->name('submit');
            Route::post('/assign', [ActivityBudgetController::class, 'assign'])->name('assign');
            Route::post('/review', [ActivityBudgetController::class, 'review'])->name('review');
            Route::post('/approve', [ActivityBudgetController::class, 'approve'])->name('approve');
            Route::post('/reject', [ActivityBudgetController::class, 'reject'])->name('reject');
            Route::post('/cancel', [ActivityBudgetController::class, 'cancel'])->name('cancel');

            // Reporting - print (browser print-to-PDF) and CSV export.
            Route::get('/print', [ActivityBudgetController::class, 'print'])->name('print');
            Route::get('/export', [ActivityBudgetController::class, 'exportCsv'])->name('export');
        });

        // Activity Retirement module - accounting for how an approved
        // budget's advance was actually spent, plus receipt uploads.
        // Scoped to data entry only for now (start + save actuals +
        // receipts) - Submit/Assign/Review/Approve/Reject/Cancel workflow
        // for the retirement itself is a later phase, same split as the
        // Budget module's own builder (Phase 4) vs workflow (Phase 6).
        Route::prefix('/{activity}/retirement')->name('retirement.')->group(function () {
            Route::get('/', [ActivityRetirementController::class, 'edit'])->name('edit');
            Route::post('/', [ActivityRetirementController::class, 'store'])->name('store');
            Route::put('/', [ActivityRetirementController::class, 'update'])->name('update');

            Route::post('/documents', [ActivityRetirementController::class, 'uploadDocument'])->name('documents.store');
            Route::get('/documents/{document}/download', [ActivityRetirementController::class, 'downloadDocument'])->name('documents.download');

            // Reporting - print (browser print-to-PDF) and CSV export.
            Route::get('/print', [ActivityRetirementController::class, 'print'])->name('print');
            Route::get('/export', [ActivityRetirementController::class, 'exportCsv'])->name('export');
        });
    });

    // Activity Budget module - master data admin (Phase 2). Admin-only,
    // layered on top of the group's own role:admin,staff so staff never
    // reach these routes at all, not just have the buttons hidden.
    Route::prefix('budget-settings')->name('budget-settings.')->middleware('role:admin')->group(function () {
        Route::prefix('activity-types')->name('activity-types.')->group(function () {
            Route::get('/', [ActivityTypeController::class, 'index'])->name('index');
            Route::post('/', [ActivityTypeController::class, 'store'])->name('store');
            Route::put('/{activity_type}', [ActivityTypeController::class, 'update'])->name('update');
            Route::post('/{activity_type}/toggle-active', [ActivityTypeController::class, 'toggleActive'])->name('toggle-active');
        });

        Route::prefix('budget-categories')->name('budget-categories.')->group(function () {
            Route::get('/', [BudgetCategoryController::class, 'index'])->name('index');
            Route::post('/', [BudgetCategoryController::class, 'store'])->name('store');
            Route::put('/{budget_category}', [BudgetCategoryController::class, 'update'])->name('update');
            Route::post('/{budget_category}/toggle-active', [BudgetCategoryController::class, 'toggleActive'])->name('toggle-active');
        });

        Route::prefix('budget-components')->name('budget-components.')->group(function () {
            Route::get('/', [BudgetComponentController::class, 'index'])->name('index');
            Route::post('/', [BudgetComponentController::class, 'store'])->name('store');
            Route::put('/{budget_component}', [BudgetComponentController::class, 'update'])->name('update');
            Route::post('/{budget_component}/toggle-active', [BudgetComponentController::class, 'toggleActive'])->name('toggle-active');
        });
    });
});
