<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\Booking;
use App\Models\EvaluationReport;
use App\Models\InterviewerProfile;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();

        if ($user->isInterviewer()) {
            return $this->interviewerDashboard($user);
        }

        return $this->candidateDashboard($user);
    }

    private function candidateDashboard($user): View
    {
        $upcomingBookings = Booking::where('candidate_id', $user->id)
            ->whereIn('status', ['pending', 'accepted'])
            ->where('scheduled_at', '>', now())
            ->with('interviewer:id,name,profile_picture')
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get();

        $recentEvaluations = EvaluationReport::where('candidate_id', $user->id)
            ->where('is_shared_with_candidate', true)
            ->with('interviewer:id,name')
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        $totalInterviews     = Booking::where('candidate_id', $user->id)->where('status', 'completed')->count();
        $averageScore        = EvaluationReport::where('candidate_id', $user->id)->avg('overall_score');
        $unreadNotifications = AppNotification::where('user_id', $user->id)->where('is_read', false)->count();

        $featuredInterviewers = InterviewerProfile::with('user')
            ->where('is_approved', true)
            ->where('is_available', true)
            ->orderByDesc('average_rating')
            ->limit(6)
            ->get();

        return view('dashboard.candidate', compact(
            'user',
            'upcomingBookings',
            'recentEvaluations',
            'totalInterviews',
            'averageScore',
            'unreadNotifications',
            'featuredInterviewers'
        ));
    }

    private function interviewerDashboard($user): View
    {
        $pendingBookings = Booking::where('interviewer_id', $user->id)
            ->where('status', 'pending')
            ->with('candidate:id,name,profile_picture')
            ->orderBy('scheduled_at')
            ->get();

        $upcomingBookings = Booking::where('interviewer_id', $user->id)
            ->where('status', 'accepted')
            ->where('scheduled_at', '>', now())
            ->with('candidate:id,name,profile_picture')
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get();

        $totalSessions   = Booking::where('interviewer_id', $user->id)->where('status', 'completed')->count();
        $totalEarnings   = Booking::where('interviewer_id', $user->id)
            ->where('status', 'completed')
            ->where('payment_status', 'paid')
            ->sum('amount');
        $unreadNotifications = AppNotification::where('user_id', $user->id)->where('is_read', false)->count();

        $profile = $user->interviewerProfile;

        return view('dashboard.interviewer', compact(
            'user',
            'pendingBookings',
            'upcomingBookings',
            'totalSessions',
            'totalEarnings',
            'unreadNotifications',
            'profile'
        ));
    }
}
