<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function checkout(string $bookingId): RedirectResponse
    {
        $user = auth()->user();
        $booking = Booking::with('interviewer')
            ->where('candidate_id', $user->id)
            ->findOrFail($bookingId);

        if ($booking->payment_status === 'paid') {
            return redirect()->route('bookings.show', $booking)->with('success', 'This booking is already paid.');
        }

        if (in_array($booking->status, ['cancelled', 'rejected'], true)) {
            return redirect()->route('bookings.show', $booking)->with('error', 'Cannot pay for a cancelled or rejected booking.');
        }

        $secret = config('services.stripe.secret');
        if (!$secret) {
            return redirect()->route('bookings.show', $booking)->with('error', 'Stripe is not configured.');
        }

        $response = Http::withToken($secret)
            ->asForm()
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => 'payment',
                'client_reference_id' => $booking->id,
                'customer_email' => $user->email,
                'success_url' => route('payments.success', $booking) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('bookings.show', $booking),
                'payment_method_types' => ['card'],
                'metadata' => [
                    'booking_id' => $booking->id,
                    'candidate_id' => $booking->candidate_id,
                    'interviewer_id' => $booking->interviewer_id,
                ],
                'line_items' => [[
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => strtolower($booking->currency),
                        'unit_amount' => (int) round($booking->amount * 100),
                        'product_data' => [
                            'name' => 'Mock interview session',
                            'description' => $booking->interviewer?->name
                                ? "Interview with {$booking->interviewer->name}"
                                : 'Technical interview session',
                        ],
                    ],
                ]],
            ]);

        if ($response->failed()) {
            Log::warning('Stripe checkout session creation failed', [
                'booking_id' => $booking->id,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            return redirect()->route('bookings.show', $booking)->with('error', 'Unable to start payment. Please try again.');
        }

        $session = $response->json();
        $booking->update(['stripe_session_id' => $session['id'] ?? null]);

        return redirect()->away($session['url']);
    }

    public function success(Request $request, string $bookingId): RedirectResponse
    {
        $booking = Booking::where('candidate_id', auth()->id())->findOrFail($bookingId);
        $sessionId = $request->query('session_id');

        if (!$sessionId) {
            return redirect()->route('bookings.show', $booking)->with('error', 'Missing Stripe session.');
        }

        $response = Http::withToken(config('services.stripe.secret'))
            ->get("https://api.stripe.com/v1/checkout/sessions/{$sessionId}");

        if ($response->failed()) {
            return redirect()->route('bookings.show', $booking)->with('error', 'Could not verify payment.');
        }

        $session = $response->json();
        if (($session['client_reference_id'] ?? null) !== $booking->id) {
            return redirect()->route('bookings.show', $booking)->with('error', 'Payment session does not match this booking.');
        }

        if (($session['payment_status'] ?? null) === 'paid') {
            $booking->update([
                'payment_status' => 'paid',
                'stripe_session_id' => $session['id'] ?? $sessionId,
                'payment_intent_id' => $session['payment_intent'] ?? null,
            ]);

            return redirect()->route('bookings.show', $booking)->with('success', 'Payment completed successfully.');
        }

        return redirect()->route('bookings.show', $booking)->with('error', 'Payment was not completed.');
    }

    public function webhook(Request $request)
    {
        if (!$this->hasValidStripeSignature($request)) {
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        $event = $request->json()->all();

        if (($event['type'] ?? null) === 'checkout.session.completed') {
            $session = $event['data']['object'] ?? [];
            $bookingId = $session['client_reference_id'] ?? $session['metadata']['booking_id'] ?? null;

            if ($bookingId) {
                Booking::where('id', $bookingId)->update([
                    'payment_status' => 'paid',
                    'stripe_session_id' => $session['id'] ?? null,
                    'payment_intent_id' => $session['payment_intent'] ?? null,
                ]);
            }
        }

        return response()->json(['received' => true]);
    }

    private function hasValidStripeSignature(Request $request): bool
    {
        $secret = config('services.stripe.webhook_secret');
        if (!$secret) {
            return true;
        }

        $signature = $request->header('Stripe-Signature', '');
        $timestamp = Str::of($signature)->match('/(?:^|,\s*)t=([^,]+)/')->toString();
        $expected = Str::of($signature)->match('/(?:^|,\s*)v1=([^,]+)/')->toString();

        if (!$timestamp || !$expected) {
            return false;
        }

        $payload = $timestamp . '.' . $request->getContent();
        $actual = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $actual);
    }
}
