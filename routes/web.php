<?php

use App\Livewire\ApprovePaymentRequest;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});

/*
|--------------------------------------------------------------------------
| Approval Routes (Temporary Signed URLs)
|--------------------------------------------------------------------------
|
| These routes allow approvers to approve/reject payment requests
| via temporary signed URLs sent in email notifications.
|
*/

Route::middleware(['signed'])->group(function () {

    /**
     * Approval page route
     *
     * This route uses Laravel's temporary signed URL feature
     * URL expires after 7 days by default (can be customized)
     *
     * Example URL:
     * /approve-payment-request/1/5?expires=...&signature=...
     */
    Route::get('/approve-payment-request/{requestId}/{approvalHistoryId}', ApprovePaymentRequest::class)
        ->name('approve-payment-request');
});

/**
 * Optional: Invalid signature error page
 *
 * When signature is invalid or expired, redirect to this page
 */
Route::get('/approval-link-invalid', function () {
    return view('errors.approval-link-invalid');
})->name('approval-link-invalid');
