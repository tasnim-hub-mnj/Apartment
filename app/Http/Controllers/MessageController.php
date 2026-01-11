<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;

class MessageController extends Controller
{
    public function store(Request $request,int $conversationId)// إرسال رسالة جديدة
    {
        $conversation = Conversation::findOrFail($conversationId);
        // تحقق أن المستخدم جزء من المحادثة
        if (!in_array(Auth::id(),[$conversation->owner_id,$conversation->renter_id]))
        {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $message = $conversation->messages()->create([
            'conversation_id' => $conversationId,
            'sender_id' => Auth::id(),
            'content'=> $request->content,
        ]);

        // تحديد الطرف الآخر لإرسال إشعار
        $receiverId = ($conversation->owner_id == Auth::id())
            ? $conversation->renter_id
            : $conversation->owner_id;

        NotificationService::send(// إرسال إشعار للطرف الآخر بوجود رسالة جديدة
            $receiverId,
            'New Message',
            'You have received a new message'
        );

        return response()->json([
            'message' => 'Message sent successfully',
            'data'    => $message
        ],201);
    }
    //____________________________________________________________
    public function getMessagesInConvers(int $conversationId)// جلب كل الرسائل في محادثة معينة
    {
        $conversation = Conversation::with('messages.sender')->findOrFail($conversationId);
        // تحقق أن المستخدم جزء من المحادثة
        if (!in_array(Auth::id(), [$conversation->owner_id, $conversation->renter_id]))
        {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'messages :' => $conversation
            ->messages()
            ->orderByDesc('created_at')
            ->get()
        ],200);
    }
    //____________________________________________________________
    // public function getMessage($messageId)// عرض رسالة واحدة
    // {
    //     $message = Message::with('sender')->findOrFail($messageId);
    //     // تحقق أن المستخدم جزء من المحادثة
    //     if (!in_array(Auth::id(),[$message->conversation->owner_id,$message->conversation->renter_id]))
    //     {
    //         return response()->json(['message' => 'Unauthorized'], 403);
    //     }

    //     return response()->json([
    //         'message :' => $message
    //     ],200);
    // }
    // //____________________________________________________________
    // public function destroy($messageId)// حذف رسالة
    // {
    //     $message = Message::findOrFail($messageId);
    //     // فقط المرسل يقدر يحذف رسالته
    //     if ($message->sender_id !== Auth::id())
    //     {
    //         return response()->json(['message' => 'Unauthorized'],403);
    //     }
    //     $message->delete();

    //     return response()->json([
    //         'message' => 'Message deleted successfully'
    //     ],200);
    // }
    // //____________________________________________________________
    
}
