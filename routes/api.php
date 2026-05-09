<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Booking\BookingController;
use App\Http\Controllers\Evaluation\EvaluationController;
use App\Http\Controllers\Interviewer\AvailabilityController;
use App\Http\Controllers\Interviewer\InterviewerController;
use App\Http\Controllers\Messaging\MessageController;
use App\Http\Controllers\Notification\NotificationController;
use App\Http\Controllers\Web\PaymentController;
use App\Http\Controllers\Submission\SubmissionController;
use Illuminate\Support\Facades\Route;

// ─── Health Check ────────────────────────────────────────────────────────────
Route::get('/health', fn() => response()->json([
    'status'  => 'ok',
    'service' => 'HireSphere API',
    'version' => '1.0.0',
    'time'    => now()->toIso8601String(),
]));

Route::post('/stripe/webhook', [PaymentController::class, 'webhook']);

// ─── Authentication (Public) ─────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/register',        [AuthController::class, 'register']);
    Route::post('/confirm',         [AuthController::class, 'confirm']);
    Route::post('/login',           [AuthController::class, 'login']);
    Route::post('/refresh',         [AuthController::class, 'refresh']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password',  [AuthController::class, 'resetPassword']);
});

// ─── Public Interviewer Browsing ─────────────────────────────────────────────
Route::prefix('interviewers')->group(function () {
    Route::get('/',                                 [InterviewerController::class, 'index']);
    Route::get('/{id}',                             [InterviewerController::class, 'show']);
    Route::get('/{id}/reviews',                     [InterviewerController::class, 'reviews']);
    Route::get('/{interviewerId}/availability',     [AvailabilityController::class, 'index']);
});

// ─── Authenticated Routes ─────────────────────────────────────────────────────
Route::middleware('cognito.auth')->group(function () {

    // ── Current User ──────────────────────────────────────────────────────────
    Route::get('/auth/me',     [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // ── Notifications ─────────────────────────────────────────────────────────
    Route::prefix('notifications')->group(function () {
        Route::get('/',           [NotificationController::class, 'index']);
        Route::put('/read-all',   [NotificationController::class, 'markAllRead']);
        Route::put('/{id}/read',  [NotificationController::class, 'markRead']);
    });

    // ── Messaging ─────────────────────────────────────────────────────────────
    Route::prefix('conversations')->group(function () {
        Route::get('/',                        [MessageController::class, 'conversations']);
        Route::get('/{userId}/messages',       [MessageController::class, 'messages']);
        Route::post('/{userId}/messages',      [MessageController::class, 'send']);
    });

    // ── Candidate-only Routes ─────────────────────────────────────────────────
    Route::middleware('role:candidate')->group(function () {
        // Payments
        Route::post('/payments/checkout/{bookingId}', [PaymentController::class, 'apiCheckout']);
        Route::get('/payments/verify/{bookingId}',    [PaymentController::class, 'apiVerify']);

        // Bookings
        Route::post('/bookings',                  [BookingController::class, 'store']);
        Route::put('/bookings/{id}/cancel',       [BookingController::class, 'cancel']);

        // Submissions
        Route::post('/submissions',               [SubmissionController::class, 'store']);
    });

    // ── Interviewer-only Routes ───────────────────────────────────────────────
    Route::middleware('role:interviewer')->group(function () {
        // Profile management
        Route::put('/interviewers/{id}',              [InterviewerController::class, 'update']);

        // Availability
        Route::get('/interviewer/availability',             [AvailabilityController::class, 'mySlots']);
        Route::post('/interviewer/availability',            [AvailabilityController::class, 'store']);
        Route::delete('/interviewer/availability/{slotId}', [AvailabilityController::class, 'destroy']);

        // Booking actions
        Route::put('/bookings/{id}/accept',           [BookingController::class, 'accept']);
        Route::put('/bookings/{id}/reject',           [BookingController::class, 'reject']);
        Route::put('/bookings/{id}/complete',         [BookingController::class, 'complete']);
        Route::put('/bookings/{id}/room-url',         [BookingController::class, 'updateRoomUrl']);

        // Evaluations
        Route::post('/evaluations',                   [EvaluationController::class, 'store']);

        // Annotate submissions
        Route::put('/submissions/{id}/annotate',      [SubmissionController::class, 'annotate']);
    });

    // ── Admin-only Routes ─────────────────────────────────────────────────────
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/interviewers/pending',        [AdminController::class, 'pendingInterviewers']);
        Route::put('/interviewers/{id}/approve',   [AdminController::class, 'approveInterviewer']);
        Route::put('/interviewers/{id}/reject',    [AdminController::class, 'rejectInterviewer']);
        Route::get('/users',                       [AdminController::class, 'users']);
    });

    // ── Shared Authenticated Routes ───────────────────────────────────────────
    Route::get('/bookings',                           [BookingController::class, 'index']);
    Route::get('/bookings/{id}',                      [BookingController::class, 'show']);
    Route::post('/bookings/{id}/join',                [BookingController::class, 'joinSession']);
    Route::put('/bookings/{id}/cancel',               [BookingController::class, 'cancel']);
    Route::post('/bookings/{bookingId}/review',       [EvaluationController::class, 'submitReview']);

    Route::get('/submissions',                        [SubmissionController::class, 'index']);
    Route::get('/submissions/{id}',                   [SubmissionController::class, 'show']);
    Route::get('/submissions/{id}/download',          [SubmissionController::class, 'download']);

    Route::get('/evaluations',                        [EvaluationController::class, 'index']);
    Route::get('/evaluations/{id}',                   [EvaluationController::class, 'show']);
});
