<?php

namespace App\Http\Controllers\Messaging;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DynamoDBService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Messaging", description="Real-time messaging between candidates and interviewers")
 */
class MessageController extends Controller
{
    public function __construct(
        private readonly DynamoDBService $dynamoDB,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * @OA\Get(
     *   path="/api/conversations",
     *   tags={"Messaging"},
     *   summary="Get all conversations for current user",
     *   security={{"bearerAuth":{}}},
     * )
     */
    public function conversations(Request $request): JsonResponse
    {
        $user          = $request->user();
        $conversations = $this->dynamoDB->getUserConversations($user->id);

        // Enrich with other participant's profile
        foreach ($conversations as &$conv) {
            $otherId = collect($conv['participants'])
                ->reject(fn($id) => $id === $user->id)
                ->first();

            if ($otherId) {
                $other = User::select('id', 'name', 'profile_picture', 'role')
                    ->find($otherId);
                $conv['other_participant'] = $other;
            }
        }

        return response()->json($conversations);
    }

    /**
     * @OA\Get(
     *   path="/api/conversations/{userId}/messages",
     *   tags={"Messaging"},
     *   summary="Get messages in a conversation",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="userId", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer", default=50)),
     *   @OA\Parameter(name="last_key", in="query", @OA\Schema(type="string")),
     * )
     */
    public function messages(Request $request, string $userId): JsonResponse
    {
        $user         = $request->user();
        $conversation = $this->dynamoDB->getOrCreateConversation($user->id, $userId);

        $limit   = min((int) $request->get('limit', 50), 100);
        $lastKey = $request->get('last_key');

        $result = $this->dynamoDB->getMessages(
            $conversation['conversation_id'],
            $limit,
            $lastKey
        );

        // Mark messages as read
        $this->dynamoDB->markMessagesAsRead($conversation['conversation_id'], $user->id);

        return response()->json([
            'conversation' => $conversation,
            'messages'     => $result['messages'],
            'last_key'     => $result['last_key'],
        ]);
    }

    /**
     * @OA\Post(
     *   path="/api/conversations/{userId}/messages",
     *   tags={"Messaging"},
     *   summary="Send a message",
     *   security={{"bearerAuth":{}}},
     * )
     */
    public function send(Request $request, string $userId): JsonResponse
    {
        $user = $request->user();

        if ($user->id === $userId) {
            return response()->json(['message' => 'Cannot message yourself.'], 422);
        }

        $recipient = User::findOrFail($userId);

        $data = $request->validate([
            'content' => 'required|string|max:5000',
            'type'    => 'in:text,file,code',
        ]);

        $conversation = $this->dynamoDB->getOrCreateConversation($user->id, $userId);

        $message = $this->dynamoDB->sendMessage(
            $conversation['conversation_id'],
            $user->id,
            $data['content'],
            $data['type'] ?? 'text'
        );

        // Notify recipient
        $this->notifications->sendNewMessage($recipient, $user);

        return response()->json($message, 201);
    }
}
