<?php

namespace App\Http\Controllers\Interviewer;

use App\Http\Controllers\Controller;
use App\Models\AvailabilitySlot;
use App\Models\InterviewerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Availability", description="Interviewer availability slot management")
 */
class AvailabilityController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/interviewers/{interviewerId}/availability",
     *   tags={"Availability"},
     *   summary="List available slots for an interviewer",
     * )
     */
    public function index(Request $request, string $interviewerId): JsonResponse
    {
        $profile = InterviewerProfile::findOrFail($interviewerId);

        $query = $profile->availabilitySlots()
            ->where('start_time', '>', now());

        if ($request->boolean('available_only', true)) {
            $query->where('status', 'available');
        }

        if ($request->has('from')) {
            $query->where('start_time', '>=', $request->from);
        }

        if ($request->has('to')) {
            $query->where('start_time', '<=', $request->to);
        }

        $slots = $query->orderBy('start_time')->get();

        return response()->json($slots);
    }

    /**
     * @OA\Post(
     *   path="/api/interviewer/availability",
     *   tags={"Availability"},
     *   summary="Add availability slots",
     *   security={{"bearerAuth":{}}},
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = InterviewerProfile::where('user_id', $user->id)->firstOrFail();

        $data = $request->validate([
            'slots'                => 'required|array|min:1|max:50',
            'slots.*.start_time'   => 'required|date|after:now',
            'slots.*.end_time'     => 'required|date|after:slots.*.start_time',
            'slots.*.timezone'     => 'required|string|timezone',
            'slots.*.is_recurring' => 'boolean',
        ]);

        $created = [];
        foreach ($data['slots'] as $slot) {
            $existing = $profile->availabilitySlots()
                ->where('status', 'available')
                ->where(function ($q) use ($slot) {
                    $q->whereBetween('start_time', [$slot['start_time'], $slot['end_time']])
                      ->orWhereBetween('end_time', [$slot['start_time'], $slot['end_time']]);
                })->exists();

            if (!$existing) {
                $created[] = $profile->availabilitySlots()->create($slot);
            }
        }

        return response()->json(['created' => $created, 'count' => count($created)], 201);
    }

    /**
     * @OA\Delete(
     *   path="/api/interviewer/availability/{slotId}",
     *   tags={"Availability"},
     *   summary="Delete an availability slot",
     *   security={{"bearerAuth":{}}},
     * )
     */
    public function destroy(Request $request, string $slotId): JsonResponse
    {
        $user    = $request->user();
        $profile = InterviewerProfile::where('user_id', $user->id)->firstOrFail();

        $slot = $profile->availabilitySlots()->findOrFail($slotId);

        if ($slot->status === 'booked') {
            return response()->json(['message' => 'Cannot delete a booked slot.'], 422);
        }

        $slot->delete();

        return response()->json(['message' => 'Slot deleted.']);
    }
}
