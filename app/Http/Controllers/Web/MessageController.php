<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DynamoDBService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function __construct(
        private readonly DynamoDBService $dynamoDB,
        private readonly NotificationService $notifications,
    ) {}

    public function index(): View
    {
        $user          = auth()->user();
        $conversations = $this->dynamoDB->getUserConversations($user->id);

        foreach ($conversations as &$conv) {
            $otherId = collect($conv['participants'])->reject(fn($id) => $id === $user->id)->first();
            if ($otherId) {
                $conv['other_participant'] = User::select('id', 'name', 'profile_picture', 'role')->find($otherId);
            }
        }

        return view('messages.index', compact('conversations'));
    }

    public function conversation(Request $request, string $userId): View
    {
        $user         = auth()->user();
        $other        = User::findOrFail($userId);
        $conversation = $this->dynamoDB->getOrCreateConversation($user->id, $userId);
        $result       = $this->dynamoDB->getMessages($conversation['conversation_id']);

        $this->dynamoDB->markMessagesAsRead($conversation['conversation_id'], $user->id);

        $conversations = $this->dynamoDB->getUserConversations($user->id);
        foreach ($conversations as &$conv) {
            $otherId = collect($conv['participants'])->reject(fn($id) => $id === $user->id)->first();
            if ($otherId) {
                $conv['other_participant'] = User::select('id', 'name', 'profile_picture', 'role')->find($otherId);
            }
        }

        return view('messages.index', [
            'conversations'    => $conversations,
            'activeConversation' => $conversation,
            'messages'         => $result['messages'],
            'otherUser'        => $other,
        ]);
    }

    public function send(Request $request, string $userId): JsonResponse
    {
        $user = auth()->user();
        $data = $request->validate(['content' => 'required|string|max:5000']);

        $recipient    = User::findOrFail($userId);
        $conversation = $this->dynamoDB->getOrCreateConversation($user->id, $userId);
        $message      = $this->dynamoDB->sendMessage($conversation['conversation_id'], $user->id, $data['content']);

        $this->notifications->sendNewMessage($recipient, $user);

        return response()->json($message);
    }
}
