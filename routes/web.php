<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\BookingController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\EvaluationController;
use App\Http\Controllers\Web\InterviewerController;
use App\Http\Controllers\Web\MessageController;
use App\Http\Controllers\Web\PaymentController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\SubmissionController;
use Illuminate\Support\Facades\Route;

// ─── Guest (unauthenticated) routes ──────────────────────────────────────────
Route::middleware('guest.web')->group(function () {
    Route::get('/',              [AuthController::class, 'showLogin'])->name('home');
    Route::get('/login',         [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',        [AuthController::class, 'login'])->name('login.post');
    Route::get('/register',      [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register',     [AuthController::class, 'register'])->name('register.post');
    Route::get('/confirm',       [AuthController::class, 'showConfirm'])->name('auth.confirm');
    Route::post('/confirm',      [AuthController::class, 'confirm'])->name('auth.confirm.post');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.email');
    Route::get('/reset-password',  [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

// ─── Authenticated routes ─────────────────────────────────────────────────────
Route::middleware('web.auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile',      [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile',      [ProfileController::class, 'update'])->name('profile.update');

    // Interviewer availability (interviewer-only)
    Route::get('/availability',              [ProfileController::class, 'availability'])->name('availability.index');
    Route::post('/availability',             [ProfileController::class, 'storeSlot'])->name('availability.store');
    Route::delete('/availability/{slotId}',  [ProfileController::class, 'destroySlot'])->name('availability.destroy');

    // Interviewers (browse)
    Route::get('/interviewers',      [InterviewerController::class, 'index'])->name('interviewers.index');
    Route::get('/interviewers/{id}', [InterviewerController::class, 'show'])->name('interviewers.show');

    // Bookings
    Route::get('/bookings',                            [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/new/{interviewerId}',        [BookingController::class, 'create'])->name('bookings.create');
    Route::post('/bookings',                           [BookingController::class, 'store'])->name('bookings.store');
    Route::post('/bookings/{id}/pay',                  [PaymentController::class, 'checkout'])->name('payments.checkout');
    Route::get('/bookings/{id}/payment/success',       [PaymentController::class, 'success'])->name('payments.success');
    Route::get('/bookings/{id}',                       [BookingController::class, 'show'])->name('bookings.show');
    Route::post('/bookings/{id}/accept',               [BookingController::class, 'accept'])->name('bookings.accept');
    Route::post('/bookings/{id}/reject',               [BookingController::class, 'reject'])->name('bookings.reject');
    Route::post('/bookings/{id}/cancel',               [BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::get('/bookings/{id}/join',                  [BookingController::class, 'join'])->name('bookings.join');

    // Submissions
    Route::get('/submissions',              [SubmissionController::class, 'index'])->name('submissions.index');
    Route::get('/submissions/new',          [SubmissionController::class, 'create'])->name('submissions.create');
    Route::post('/submissions',             [SubmissionController::class, 'store'])->name('submissions.store');
    Route::get('/submissions/{id}',         [SubmissionController::class, 'show'])->name('submissions.show');
    Route::post('/submissions/{id}/annotate', [SubmissionController::class, 'annotate'])->name('submissions.annotate');

    // Evaluations
    Route::get('/evaluations',                         [EvaluationController::class, 'index'])->name('evaluations.index');
    Route::get('/evaluations/{id}',                    [EvaluationController::class, 'show'])->name('evaluations.show');
    Route::get('/bookings/{bookingId}/evaluate',       [EvaluationController::class, 'create'])->name('evaluations.create');
    Route::post('/evaluations',                        [EvaluationController::class, 'store'])->name('evaluations.store');
    Route::post('/bookings/{bookingId}/review',        [EvaluationController::class, 'submitReview'])->name('bookings.review');

    // Messaging
    Route::get('/messages',                            [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{userId}',                   [MessageController::class, 'conversation'])->name('messages.conversation');
    Route::post('/messages/{userId}',                  [MessageController::class, 'send'])->name('messages.send');
});
