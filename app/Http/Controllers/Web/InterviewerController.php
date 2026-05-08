<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\InterviewerProfile;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InterviewerController extends Controller
{
    public function index(Request $request): View
    {
        $query = InterviewerProfile::with('user')->where('is_approved', true);

        if ($request->filled('domain')) {
            $query->whereJsonContains('domains', $request->domain);
        }
        if ($request->filled('interview_type')) {
            $query->whereJsonContains('interview_types', $request->interview_type);
        }
        if ($request->filled('experience_level')) {
            $query->where('experience_level', $request->experience_level);
        }
        if ($request->filled('min_rating')) {
            $query->where('average_rating', '>=', (float) $request->min_rating);
        }
        if ($request->filled('max_rate')) {
            $query->where('hourly_rate', '<=', (float) $request->max_rate);
        }
        if ($request->boolean('available_only')) {
            $query->where('is_available', true)->whereHas('availableSlots');
        }

        $interviewers = $query->orderByDesc('average_rating')->paginate(12)->withQueryString();

        return view('interviewers.index', compact('interviewers'));
    }

    public function show(string $id): View
    {
        $profile = InterviewerProfile::with(['user', 'availableSlots' => function ($q) {
            $q->where('status', 'available')->where('start_time', '>', now())->orderBy('start_time');
        }])->findOrFail($id);

        $reviews = Review::with('reviewer:id,name,profile_picture')
            ->where('reviewee_id', $profile->user_id)
            ->where('is_public', true)
            ->orderByDesc('created_at')
            ->paginate(5);

        return view('interviewers.show', compact('profile', 'reviews'));
    }
}
