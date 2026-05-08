<?php

namespace App\Http\Controllers\Submission;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Services\S3Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Submissions", description="Coding challenge submission management")
 */
class SubmissionController extends Controller
{
    public function __construct(private readonly S3Service $s3) {}

    /**
     * @OA\Get(path="/api/submissions", tags={"Submissions"}, summary="Get all submissions for current user", security={{"bearerAuth":{}}})
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = $user->isCandidate()
            ? Submission::where('candidate_id', $user->id)
            : Submission::whereHas('booking', fn($q) => $q->where('interviewer_id', $user->id));

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('booking_id')) {
            $query->where('booking_id', $request->booking_id);
        }

        return response()->json($query->with(['booking'])->orderByDesc('created_at')->paginate(10));
    }

    /**
     * @OA\Post(
     *   path="/api/submissions",
     *   tags={"Submissions"},
     *   summary="Submit a coding solution",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data",
     *     @OA\Schema(
     *       @OA\Property(property="title", type="string"),
     *       @OA\Property(property="submission_type", type="string", enum={"file_upload","github_link","inline_code"}),
     *       @OA\Property(property="file", type="string", format="binary"),
     *       @OA\Property(property="github_url", type="string"),
     *       @OA\Property(property="inline_code", type="string"),
     *     )
     *   ))
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isCandidate()) {
            return response()->json(['message' => 'Only candidates can submit solutions.'], 403);
        }

        $data = $request->validate([
            'title'           => 'required|string|max:200',
            'description'     => 'nullable|string|max:2000',
            'booking_id'      => 'nullable|uuid|exists:bookings,id',
            'language'        => 'nullable|string|max:50',
            'submission_type' => 'required|in:file_upload,github_link,inline_code',
            'file'            => 'required_if:submission_type,file_upload|file|max:51200',
            'github_url'      => 'required_if:submission_type,github_link|nullable|url',
            'github_branch'   => 'nullable|string|max:100',
            'inline_code'     => 'required_if:submission_type,inline_code|nullable|string',
        ]);

        $submissionData = [
            'candidate_id'    => $user->id,
            'booking_id'      => $data['booking_id'] ?? null,
            'title'           => $data['title'],
            'description'     => $data['description'] ?? null,
            'language'        => $data['language'] ?? null,
            'submission_type' => $data['submission_type'],
            'github_url'      => $data['github_url'] ?? null,
            'github_branch'   => $data['github_branch'] ?? null,
            'inline_code'     => $data['inline_code'] ?? null,
        ];

        if ($data['submission_type'] === 'file_upload' && $request->hasFile('file')) {
            $uploaded = $this->s3->uploadSubmission($request->file('file'), $user->id);
            $submissionData['file_path'] = $uploaded['key'];
            $submissionData['file_name'] = $uploaded['file_name'];
            $submissionData['file_size'] = $uploaded['file_size'];
        }

        $submission = Submission::create($submissionData);

        return response()->json($submission, 201);
    }

    /**
     * @OA\Get(path="/api/submissions/{id}", tags={"Submissions"}, summary="Get submission details", security={{"bearerAuth":{}}})
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user       = $request->user();
        $submission = Submission::with(['booking', 'reviewer'])->findOrFail($id);

        $canView = $user->id === $submission->candidate_id
            || ($submission->booking && $user->id === $submission->booking->interviewer_id);

        if (!$canView) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        // Add presigned download URL for file submissions
        if ($submission->file_path) {
            $submission->download_url = $this->s3->getPresignedDownloadUrl($submission->file_path);
        }

        return response()->json($submission);
    }

    /**
     * @OA\Put(
     *   path="/api/submissions/{id}/annotate",
     *   tags={"Submissions"},
     *   summary="Add annotation to a submission (interviewer only)",
     *   security={{"bearerAuth":{}}},
     * )
     */
    public function annotate(Request $request, string $id): JsonResponse
    {
        $user       = $request->user();
        $submission = Submission::with('booking')->findOrFail($id);

        if (!$submission->booking || $user->id !== $submission->booking->interviewer_id) {
            return response()->json(['message' => 'Only the assigned interviewer can annotate.'], 403);
        }

        $data = $request->validate([
            'annotation' => 'required|string|max:5000',
        ]);

        $submission->update([
            'interviewer_annotation' => $data['annotation'],
            'status'                 => 'reviewed',
            'reviewed_by'            => $user->id,
            'reviewed_at'            => now(),
        ]);

        return response()->json($submission->fresh());
    }

    /**
     * @OA\Get(
     *   path="/api/submissions/{id}/download",
     *   tags={"Submissions"},
     *   summary="Get presigned download URL for a submission",
     *   security={{"bearerAuth":{}}},
     * )
     */
    public function download(Request $request, string $id): JsonResponse
    {
        $user       = $request->user();
        $submission = Submission::with('booking')->findOrFail($id);

        $canDownload = $user->id === $submission->candidate_id
            || ($submission->booking && $user->id === $submission->booking->interviewer_id);

        if (!$canDownload) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (!$submission->file_path) {
            return response()->json(['message' => 'No file attached to this submission.'], 404);
        }

        $url = $this->s3->getPresignedDownloadUrl($submission->file_path, 30);

        return response()->json(['download_url' => $url, 'expires_in' => 1800]);
    }
}
