<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Notifications", description="In-app notification management")
 */
class NotificationController extends Controller
{
    /**
     * @OA\Get(path="/api/notifications", tags={"Notifications"}, summary="Get user notifications", security={{"bearerAuth":{}}})
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = AppNotification::where('user_id', $user->id)
            ->when($request->boolean('unread_only'), fn($q) => $q->where('is_read', false))
            ->orderByDesc('created_at')
            ->paginate(20);

        $unreadCount = AppNotification::where('user_id', $user->id)->where('is_read', false)->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => $unreadCount,
        ]);
    }

    /**
     * @OA\Put(path="/api/notifications/{id}/read", tags={"Notifications"}, summary="Mark notification as read", security={{"bearerAuth":{}}})
     */
    public function markRead(Request $request, string $id): JsonResponse
    {
        $user         = $request->user();
        $notification = AppNotification::where('user_id', $user->id)->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['message' => 'Marked as read.']);
    }

    /**
     * @OA\Put(path="/api/notifications/read-all", tags={"Notifications"}, summary="Mark all notifications as read", security={{"bearerAuth":{}}})
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $user = $request->user();

        AppNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read.']);
    }
}
