<?php

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
});
