<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AvailabilitySlot;
use App\Models\InterviewerProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        $user = auth()->user()->load(['candidateProfile', 'interviewerProfile']);
        return view('profile.edit', compact('user'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $data = $request->validate([
            'name'         => 'required|string|max:100',
            'phone'        => 'nullable|string|max:20',
            'linkedin_url' => 'nullable|url',
            'github_url'   => 'nullable|url',
            'bio'          => 'nullable|string|max:1000',
        ]);

        $user->update($data);

        if ($user->isCandidate()) {
            $profileData = $request->validate([
                'current_role'        => 'nullable|string|max:100',
                'target_role'         => 'nullable|string|max:100',
                'years_of_experience' => 'nullable|integer|min:0|max:50',
                'education_level'     => 'nullable|string|max:100',
                'university'          => 'nullable|string|max:200',
                'skills'              => 'nullable|array',
                'preparation_level'   => 'nullable|in:beginner,intermediate,advanced',
            ]);
            $user->candidateProfile?->update($profileData);
        }

        if ($user->isInterviewer()) {
            $profileData = $request->validate([
                'current_company'          => 'nullable|string|max:100',
                'current_title'            => 'required|string|max:100',
                'years_of_experience'      => 'required|integer|min:0|max:50',
                'experience_level'         => 'required|in:senior,staff,principal,distinguished',
                'domains'                  => 'required|array',
                'interview_types'          => 'required|array',
                'hourly_rate'              => 'required|numeric|min:0',
                'session_duration_minutes' => 'required|integer|in:30,45,60,90,120',
                'interview_approach'       => 'nullable|string|max:2000',
                'offers_bundle'            => 'boolean',
                'is_available'             => 'boolean',
            ]);
            $user->interviewerProfile?->update($profileData);
        }

        return back()->with('success', 'Profile updated successfully!');
    }

    // ── Interviewer Availability ──────────────────────────────────────────────

    public function availability(): View
    {
        $user    = auth()->user();
        $profile = InterviewerProfile::where('user_id', $user->id)->firstOrFail();

        $slots = $profile->availabilitySlots()
            ->where('start_time', '>', now())
            ->orderBy('start_time')
            ->get();

        return view('interviewer.availability', compact('slots', 'profile'));
    }

    public function storeSlot(Request $request): RedirectResponse
    {
        $user    = auth()->user();
        $profile = InterviewerProfile::where('user_id', $user->id)->firstOrFail();

        $data = $request->validate([
            'start_time' => 'required|date|after:now',
            'end_time'   => 'required|date|after:start_time',
            'timezone'   => 'required|string|timezone',
        ]);

        $profile->availabilitySlots()->create([...$data, 'status' => 'available']);

        return back()->with('success', 'Availability slot added!');
    }

    public function destroySlot(string $slotId): RedirectResponse
    {
        $user    = auth()->user();
        $profile = InterviewerProfile::where('user_id', $user->id)->firstOrFail();
        $slot    = $profile->availabilitySlots()->findOrFail($slotId);

        if ($slot->status === 'booked') {
            return back()->with('error', 'Cannot delete a booked slot.');
        }

        $slot->delete();
        return back()->with('success', 'Slot removed.');
    }
}
