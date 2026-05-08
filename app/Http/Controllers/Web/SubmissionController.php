<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Submission;
use App\Services\S3Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubmissionController extends Controller
{
    public function __construct(private readonly S3Service $s3) {}

    public function index(Request $request): View
    {
        $user  = auth()->user();
        $query = $user->isCandidate()
            ? Submission::where('candidate_id', $user->id)
            : Submission::whereHas('booking', fn($q) => $q->where('interviewer_id', $user->id));

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $submissions = $query->with(['booking'])->orderByDesc('created_at')->paginate(10)->withQueryString();

        return view('submissions.index', compact('submissions'));
    }

    public function create(Request $request): View
    {
        $user     = auth()->user();
        $bookings = Booking::where('candidate_id', $user->id)
            ->whereIn('status', ['accepted', 'completed'])
            ->with('interviewer:id,name')
            ->orderByDesc('scheduled_at')
            ->get();

        $selectedBookingId = $request->query('booking_id');

        return view('submissions.create', compact('bookings', 'selectedBookingId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

        if (!$user->isCandidate()) {
            return back()->with('error', 'Only candidates can submit solutions.');
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

        $payload = [
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
            $payload['file_path'] = $uploaded['key'];
            $payload['file_name'] = $uploaded['file_name'];
            $payload['file_size'] = $uploaded['file_size'];
        }

        $submission = Submission::create($payload);

        return redirect()->route('submissions.show', $submission->id)
            ->with('success', 'Solution submitted successfully!');
    }

    public function show(string $id): View
    {
        $user       = auth()->user();
        $submission = Submission::with(['booking', 'reviewer'])->findOrFail($id);

        $canView = $user->id === $submission->candidate_id
            || ($submission->booking && $user->id === $submission->booking->interviewer_id);

        abort_unless($canView, 403);

        $downloadUrl = $submission->file_path
            ? $this->s3->getPresignedDownloadUrl($submission->file_path)
            : null;

        return view('submissions.show', compact('submission', 'downloadUrl'));
    }

    public function annotate(Request $request, string $id): RedirectResponse
    {
        $user       = auth()->user();
        $submission = Submission::with('booking')->findOrFail($id);

        abort_unless($submission->booking && $user->id === $submission->booking->interviewer_id, 403);

        $data = $request->validate(['annotation' => 'required|string|max:5000']);

        $submission->update([
            'interviewer_annotation' => $data['annotation'],
            'status'                 => 'reviewed',
            'reviewed_by'            => $user->id,
            'reviewed_at'            => now(),
        ]);

        return back()->with('success', 'Annotation saved.');
    }
}
