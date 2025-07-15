<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = $request->user();

        $query = Notification::where('user_id', $currentUser->id)
            ->with(['notifiable' => function ($morphTo) {
                $morphTo->morphWith([
                    \App\Models\User::class => [],
                    \App\Models\MusicTrack::class => ['user:id,name,avatar'],
                    \App\Models\MusicPost::class => ['user:id,name,avatar', 'track:id,title,artist'],
                    \App\Models\Playlist::class => ['user:id,name,avatar'],
                    \App\Models\Comment::class => ['user:id,name,avatar'],
                ]);
            }]);

        // Filtres
        if ($request->has('type')) {
            $query->byType($request->type);
        }

        if ($request->has('unread_only') && $request->boolean('unread_only')) {
            $query->unread();
        }

        $notifications = $query->recent()
            ->paginate($request->get('per_page', 20));

        // Ajouter des informations formatées
        $notifications->getCollection()->transform(function ($notification) {
            $notification->formatted_message = $notification->formatted_message;
            $notification->icon = $notification->icon;
            $notification->color = $notification->color;
            $notification->time_ago = $notification->created_at->diffForHumans();
            return $notification;
        });

        // Marquer comme lues
        $updated = Notification::where('user_id', $currentUser->id)
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'data' => [
                'notifications' => $notifications,
                'unread_count' => $updated
            ],
            'message' => "Toutes les notifications marquées comme lues ({$updated} notifications)"
        ]);
    }

    public function destroy(Request $request, Notification $notification)
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Notification non trouvée'
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification supprimée'
        ]);
    }

    public function getUnreadCount(Request $request)
    {
        $currentUser = $request->user();

        $count = Notification::where('user_id', $currentUser->id)
            ->unread()
            ->count();

        return response()->json([
            'success' => true,
            'data' => ['unread_count' => $count]
        ]);
    }

    public function getStats(Request $request)
    {
        $currentUser = $request->user();

        $stats = [
            'total' => Notification::where('user_id', $currentUser->id)->count(),
            'unread' => Notification::where('user_id', $currentUser->id)->unread()->count(),
            'today' => Notification::where('user_id', $currentUser->id)
                ->whereDate('created_at', today())
                ->count(),
            'this_week' => Notification::where('user_id', $currentUser->id)
                ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count(),
        ];

        $typeStats = Notification::where('user_id', $currentUser->id)
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'general_stats' => $stats,
                'type_stats' => $typeStats
            ]
        ]);
    }

    public function markAsRead(Request $request, Notification $notification)
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Notification non trouvée'
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marquée comme lue'
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        $currentUser = $request->user();

        $updated = Notification::where('user_id', $currentUser->id)
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => "Toutes les notifications marquées comme lues ({$updated} notifications)"
        ]);
    }
}
