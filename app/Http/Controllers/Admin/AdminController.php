<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InterviewerProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Admin", description="Admin-only management endpoints")
 */
class AdminController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/admin/interviewers/pending",
     *   tags={"Admin"},
     *   summary="List interviewers pending approval",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Pending interviewers"),
     *   @OA\Response(response=403, description="Forbidden"),
     * )
     */
    public function pendingInterviewers(): JsonResponse
    {
        $pending = InterviewerProfile::with('user')
            ->where('is_approved', false)
            ->orderBy('created_at', 'asc')
            ->paginate(20);

        return response()->json($pending);
    }

    /**
     * @OA\Put(
     *   path="/api/admin/interviewers/{id}/approve",
     *   tags={"Admin"},
     *   summary="Approve an interviewer",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Interviewer approved"),
     *   @OA\Response(response=404, description="Not found"),
     * )
     */
    public function approveInterviewer(string $id): JsonResponse
    {
        $profile = InterviewerProfile::with('user')
            ->where('id', $id)
            ->orWhereHas('user', fn($q) => $q->where('id', $id))
            ->firstOrFail();

        $profile->update(['is_approved' => true]);

        return response()->json([
            'message'  => 'Interviewer approved successfully.',
            'profile'  => $profile->fresh('user'),
        ]);
    }

    /**
     * @OA\Put(
     *   path="/api/admin/interviewers/{id}/reject",
     *   tags={"Admin"},
     *   summary="Reject (unapprove) an interviewer",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Interviewer rejected"),
     *   @OA\Response(response=404, description="Not found"),
     * )
     */
    public function rejectInterviewer(string $id): JsonResponse
    {
        $profile = InterviewerProfile::with('user')
            ->where('id', $id)
            ->orWhereHas('user', fn($q) => $q->where('id', $id))
            ->firstOrFail();

        $profile->update(['is_approved' => false]);

        return response()->json([
            'message' => 'Interviewer access revoked.',
            'profile' => $profile->fresh('user'),
        ]);
    }

    /**
     * @OA\Get(
     *   path="/api/admin/users",
     *   tags={"Admin"},
     *   summary="List all users",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="role", in="query", @OA\Schema(type="string", enum={"candidate","interviewer","admin"})),
     *   @OA\Response(response=200, description="User list"),
     * )
     */
    public function users(Request $request): JsonResponse
    {
        $query = User::with(['candidateProfile', 'interviewerProfile'])
            ->orderBy('created_at', 'desc');

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        return response()->json($query->paginate(20));
    }
}
