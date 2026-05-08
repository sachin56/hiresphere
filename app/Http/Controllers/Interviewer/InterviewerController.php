<?php

namespace App\Http\Controllers\Interviewer;

use App\Http\Controllers\Controller;
use App\Models\InterviewerProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Interviewers", description="Interviewer profile and search")
 */
class InterviewerController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/interviewers",
     *   tags={"Interviewers"},
     *   summary="Search and filter interviewers",
     *   @OA\Parameter(name="domain", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="interview_type", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="experience_level", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="min_rating", in="query", @OA\Schema(type="number")),
     *   @OA\Parameter(name="max_rate", in="query", @OA\Schema(type="number")),
     *   @OA\Parameter(name="available_only", in="query", @OA\Schema(type="boolean")),
     *   @OA\Response(response=200, description="List of interviewers"),
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = InterviewerProfile::with('user')
            ->where('is_approved', true);

        if ($request->has('domain')) {
            $query->whereJsonContains('domains', $request->domain);
        }

        if ($request->has('interview_type')) {
            $query->whereJsonContains('interview_types', $request->interview_type);
        }

        if ($request->has('experience_level')) {
            $query->where('experience_level', $request->experience_level);
        }

        if ($request->has('min_rating')) {
            $query->where('average_rating', '>=', (float) $request->min_rating);
        }

        if ($request->has('max_rate')) {
            $query->where('hourly_rate', '<=', (float) $request->max_rate);
        }

        if ($request->boolean('available_only', false)) {
            $query->where('is_available', true)
                ->whereHas('availableSlots');
        }

        $sortBy = $request->get('sort_by', 'average_rating');
        $sortDir = $request->get('sort_dir', 'desc');
        $allowedSorts = ['average_rating', 'hourly_rate', 'total_sessions', 'years_of_experience'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $perPage = min((int) $request->get('per_page', 12), 50);
        $interviewers = $query->paginate($perPage);

        return response()->json($interviewers);
    }

    /**
     * @OA\Get(
     *   path="/api/interviewers/{id}",
     *   tags={"Interviewers"},
     *   summary="Get interviewer details",
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Interviewer details"),
     *   @OA\Response(response=404, description="Not found"),
     * )
     */
    public function show(string $id): JsonResponse
    {
        $profile = InterviewerProfile::with(['user', 'availableSlots'])
            ->where('id', $id)
            ->orWhereHas('user', fn($q) => $q->where('id', $id))
            ->firstOrFail();

        return response()->json($profile);
    }

    /**
     * @OA\Put(
     *   path="/api/interviewers/{id}",
     *   tags={"Interviewers"},
     *   summary="Update interviewer profile",
     *   security={{"bearerAuth":{}}},
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $profile = InterviewerProfile::where('user_id', $user->id)->firstOrFail();

        $data = $request->validate([
            'current_company'         => 'nullable|string|max:100',
            'current_title'           => 'required|string|max:100',
            'years_of_experience'     => 'required|integer|min:0|max:50',
            'experience_level'        => 'required|in:senior,staff,principal,distinguished',
            'domains'                 => 'required|array',
            'domains.*'               => 'string|in:backend,frontend,devops,aiml,mobile,fullstack,security',
            'interview_types'         => 'required|array',
            'interview_types.*'       => 'string|in:dsa,system_design,behavioral,mixed',
            'specialization_badges'   => 'nullable|array',
            'hourly_rate'             => 'required|numeric|min:0',
            'session_duration_minutes' => 'required|integer|in:30,45,60,90,120',
            'interview_approach'      => 'nullable|string|max:2000',
            'offers_bundle'           => 'boolean',
            'bundle_packages'         => 'nullable|array',
            'is_available'            => 'boolean',
        ]);

        $profile->update($data);

        return response()->json($profile->fresh());
    }

    /**
     * @OA\Get(
     *   path="/api/interviewers/{id}/reviews",
     *   tags={"Interviewers"},
     *   summary="Get interviewer reviews",
     * )
     */
    public function reviews(string $id): JsonResponse
    {
        $profile = InterviewerProfile::findOrFail($id);

        $reviews = \App\Models\Review::with('reviewer:id,name,profile_picture')
            ->where('reviewee_id', $profile->user_id)
            ->where('is_public', true)
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json($reviews);
    }
}
