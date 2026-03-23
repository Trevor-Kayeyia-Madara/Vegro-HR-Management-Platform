<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\InAppNotification;
use Illuminate\Http\Request;

class InAppNotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = max((int) $request->query('per_page', 20), 1);
        $onlyUnread = filter_var($request->query('unread', false), FILTER_VALIDATE_BOOLEAN);

        $query = InAppNotification::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at');

        if ($onlyUnread) {
            $query->whereNull('read_at');
        }

        return ApiResponse::success($query->paginate($perPage));
    }

    public function markRead(Request $request, $id)
    {
        $user = $request->user();
        $notification = InAppNotification::where('user_id', $user->id)->findOrFail($id);
        $notification->update([
            'read_at' => now(),
        ]);

        return ApiResponse::success($notification, 'Notification marked as read');
    }

    public function markAllRead(Request $request)
    {
        $user = $request->user();

        InAppNotification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return ApiResponse::success(null, 'All notifications marked as read');
    }
}

