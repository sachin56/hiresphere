<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\User;
use Aws\Ses\SesClient;
use Aws\Sns\SnsClient;

class NotificationService
{
    private SesClient $ses;
    private SnsClient $sns;
    private string $snsTopicArn;

    public function __construct()
    {
        $this->ses = new SesClient([
            'version' => 'latest',
            'region'  => env('AWS_DEFAULT_REGION', 'us-east-1'),
        ]);

        $this->sns = new SnsClient([
            'version' => 'latest',
            'region'  => env('AWS_DEFAULT_REGION', 'us-east-1'),
        ]);

        $this->snsTopicArn = env('SNS_TOPIC_ARN');
    }

    public function sendBookingConfirmation(User $candidate, User $interviewer, array $booking): void
    {
        $this->createInAppNotification(
            $candidate->id,
            'booking_confirmed',
            'Interview Booked!',
            "Your interview with {$interviewer->name} is confirmed for " . date('M d, Y H:i', strtotime($booking['scheduled_at'])),
            ['booking_id' => $booking['id']]
        );

        $this->sendEmail(
            $candidate->email,
            $candidate->name,
            'Interview Booking Confirmed - HireSphere',
            $this->buildBookingConfirmationEmail($candidate->name, $interviewer->name, $booking)
        );
    }

    public function sendBookingAccepted(User $candidate, User $interviewer, array $booking): void
    {
        $this->createInAppNotification(
            $candidate->id,
            'booking_accepted',
            'Booking Accepted!',
            "{$interviewer->name} accepted your interview request.",
            ['booking_id' => $booking['id']]
        );

        $this->sendEmail(
            $candidate->email,
            $candidate->name,
            'Your Interview Request Was Accepted - HireSphere',
            $this->buildBookingAcceptedEmail($candidate->name, $interviewer->name, $booking)
        );
    }

    public function sendBookingRejected(User $candidate, User $interviewer, array $booking): void
    {
        $this->createInAppNotification(
            $candidate->id,
            'booking_rejected',
            'Booking Not Available',
            "{$interviewer->name} is unable to take your session. Please try another slot.",
            ['booking_id' => $booking['id']]
        );
    }

    public function sendEvaluationReady(User $candidate, array $evaluation): void
    {
        $this->createInAppNotification(
            $candidate->id,
            'evaluation_ready',
            'Evaluation Report Ready!',
            'Your interview evaluation report is now available.',
            ['evaluation_id' => $evaluation['id']]
        );

        $this->sendEmail(
            $candidate->email,
            $candidate->name,
            'Your Evaluation Report is Ready - HireSphere',
            $this->buildEvaluationReadyEmail($candidate->name)
        );
    }

    public function sendNewMessage(User $recipient, User $sender): void
    {
        $this->createInAppNotification(
            $recipient->id,
            'new_message',
            'New Message',
            "You have a new message from {$sender->name}.",
            ['sender_id' => $sender->id]
        );
    }

    public function createInAppNotification(
        string $userId,
        string $type,
        string $title,
        string $body,
        array $data = []
    ): AppNotification {
        return AppNotification::create([
            'user_id' => $userId,
            'type'    => $type,
            'title'   => $title,
            'body'    => $body,
            'data'    => $data,
        ]);
    }

    private function sendEmail(string $toEmail, string $toName, string $subject, string $htmlBody): void
    {
        try {
            $this->ses->sendEmail([
                'Destination' => [
                    'ToAddresses' => ["{$toName} <{$toEmail}>"],
                ],
                'Message' => [
                    'Body' => [
                        'Html' => ['Charset' => 'UTF-8', 'Data' => $htmlBody],
                    ],
                    'Subject' => ['Charset' => 'UTF-8', 'Data' => $subject],
                ],
                'Source' => config('mail.from.address'),
            ]);
        } catch (\Exception $e) {
            \Log::error('SES email failed: ' . $e->getMessage());
        }
    }

    private function buildBookingConfirmationEmail(string $candidateName, string $interviewerName, array $booking): string
    {
        $date = date('F d, Y \a\t H:i T', strtotime($booking['scheduled_at']));
        return "
        <h2>Interview Confirmed! 🎉</h2>
        <p>Hi {$candidateName},</p>
        <p>Your mock interview with <strong>{$interviewerName}</strong> has been booked.</p>
        <p><strong>Date & Time:</strong> {$date}</p>
        <p><strong>Type:</strong> " . strtoupper(str_replace('_', ' ', $booking['interview_type'])) . "</p>
        <p>Login to HireSphere to view the session link.</p>
        <br><p>Good luck! — HireSphere Team</p>
        ";
    }

    private function buildBookingAcceptedEmail(string $candidateName, string $interviewerName, array $booking): string
    {
        $date = date('F d, Y \a\t H:i T', strtotime($booking['scheduled_at']));
        return "
        <h2>Your Interview is Confirmed ✅</h2>
        <p>Hi {$candidateName},</p>
        <p><strong>{$interviewerName}</strong> has accepted your booking request.</p>
        <p><strong>Date & Time:</strong> {$date}</p>
        <p>Join the session from your HireSphere dashboard.</p>
        <br><p>Best of luck! — HireSphere Team</p>
        ";
    }

    private function buildEvaluationReadyEmail(string $candidateName): string
    {
        return "
        <h2>Your Evaluation Report is Ready 📋</h2>
        <p>Hi {$candidateName},</p>
        <p>Your interviewer has submitted your evaluation report. Visit your HireSphere dashboard to view detailed feedback.</p>
        <br><p>Keep improving! — HireSphere Team</p>
        ";
    }
}
