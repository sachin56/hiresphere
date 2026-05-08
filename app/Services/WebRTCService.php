<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WebRTCService
{
    private string $apiKey;
    private string $apiUrl;
    private string $provider;
    private string $jitsiBaseUrl;

    public function __construct()
    {
        $this->provider = env('VIDEO_PROVIDER', env('DAILY_API_KEY') ? 'daily' : 'jitsi');
        $this->apiKey = env('DAILY_API_KEY', '');
        $this->apiUrl = env('DAILY_API_URL', 'https://api.daily.co/v1');
        $this->jitsiBaseUrl = rtrim(env('JITSI_BASE_URL', 'https://meet.jit.si'), '/');
    }

    public function createRoom(string $bookingId, \DateTime $scheduledAt, int $durationMinutes): array
    {
        $roomName  = 'hiresphere-' . $bookingId;

        if ($this->provider === 'jitsi') {
            return [
                'room_id' => $roomName,
                'room_url' => "{$this->jitsiBaseUrl}/{$roomName}",
            ];
        }

        $expiresAt = $scheduledAt->getTimestamp() + ($durationMinutes * 60) + 900; // 15 min buffer

        $response = Http::withToken($this->apiKey)
            ->post("{$this->apiUrl}/rooms", [
                'name'       => $roomName,
                'privacy'    => 'private',
                'properties' => [
                    'exp'              => $expiresAt,
                    'max_participants' => 2,
                    'start_video_off'  => false,
                    'start_audio_off'  => false,
                    'enable_chat'      => true,
                    'enable_screenshare' => true,
                ],
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Failed to create WebRTC room: ' . $response->body());
        }

        $room = $response->json();

        return [
            'room_id'  => $room['name'],
            'room_url' => $room['url'],
        ];
    }

    public function createMeetingToken(string $roomName, string $userId, bool $isOwner = false): string
    {
        if ($this->provider === 'jitsi') {
            return '';
        }

        $response = Http::withToken($this->apiKey)
            ->post("{$this->apiUrl}/meeting-tokens", [
                'properties' => [
                    'room_name'  => $roomName,
                    'user_id'    => $userId,
                    'is_owner'   => $isOwner,
                    'exp'        => time() + 7200,
                ],
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Failed to create meeting token');
        }

        return $response->json('token');
    }

    public function getRecordings(string $roomName): array
    {
        if ($this->provider === 'jitsi') {
            return [];
        }

        $response = Http::withToken($this->apiKey)
            ->get("{$this->apiUrl}/recordings", ['room_name' => $roomName]);

        return $response->json('data', []);
    }

    public function deleteRoom(string $roomName): bool
    {
        if ($this->provider === 'jitsi') {
            return true;
        }

        $response = Http::withToken($this->apiKey)
            ->delete("{$this->apiUrl}/rooms/{$roomName}");

        return $response->successful();
    }
}
