<?php

namespace App\Http\Controllers;
use App\Services\FirebaseNotificationService;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function getMynotifications()//عرض الاشعارات الخاصة بالمستخدم الحالي
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'notifications' => $notifications
        ]);
    }
    //____________________________________________________________________
    public function destroy($notificationId)//حذف اشعار معين
    {
        $notification = Notification::findOrFail($notificationId);

        if ($notification->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted successfully'
        ]);
    }
    //____________________________________________________________________
 function sendOrderNotification( $token, FirebaseNotificationService $fcmService)
{

    $userToken=$token;

    $fcmService->sendNotification(
        $userToken,
        'hi',
        'mohammed',
    );

    return response()->json(['message' => 'Notification Sent!']);
}
}
