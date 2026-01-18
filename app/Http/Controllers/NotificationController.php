<?php

namespace App\Http\Controllers;
use App\Services\FirebaseNotificationService;
use App\Models\Notification;
use App\Models\Profile;
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
        ],200);
    }
    //____________________________________________________________________
    public function destroy($notificationId)//حذف اشعار معين
    {
        $notification = Notification::findOrFail($notificationId);

        if ($notification->user_id !== Auth::id())
        {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted successfully'
        ],200);
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
    //____________________________________________________________________
    public function store(Request $request)
    {
        $user_id=Auth::id();
        // التحقق من البيانات القادمة
        $validated = $request->validate([
            'title'   => 'required|string|max:255',
            'body'    => 'required|string',
        ]);

        // جلب البروفايل للمستخدم المطلوب
        $profile = Profile::where('user_id', $user_id)->firstOrFail();

        // الحصول على التوكن من جدول البروفايل
        $userToken = $profile->token_fcm;

        // إضافة الإشعار إلى قاعدة البيانات
        $notification = Notification::create([
            'user_id' => $user_id,
            'title'   => $validated['title'],
            'body'    => $validated['body'],
            'token_fcm' => $userToken,
        ]);

        return response()->json([
            'message'      => 'Notification saved and sent successfully',
            'notification' => $notification
        ],201);
    }

}
