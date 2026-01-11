<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;

class ConversationController extends Controller
{
    public function store(int $apartmentId)// إنشاء محادثة جديدة بين المستأجر والمؤجر
    {
        $renter_id=Auth::user()->id;
        $owner_id=Apartment::findOrFail($apartmentId)->user_id;
        $conversation = Conversation::create([
            'apartment_id' => $apartmentId,
            'owner_id'     => $owner_id,
            'renter_id'    => $renter_id, // المستأجر الحالي
        ]);

        $renter_name=Auth::user()->name;
        NotificationService::send(// إرسال إشعار للمؤجر بأن هناك محادثة جديدة
            $owner_id,
            'New Conversation',
            $renter_name.' has started a conversation with you'
        );

        return response()->json([
            'message' => 'Conversation created successfully',
            'data'    => $conversation
        ],201);
    }
    //____________________________________________________________
    public function getConversations()// جلب كل المحادثات الخاصة بالمستخدم الحالي
    {
        $user_id = Auth::id();

        $conversations = Conversation::where('owner_id', $user_id)
            ->orWhere('renter_id', $user_id)
            ->with([
                'owner.profile:id,user_id,first_name,last_name,image',
                'renter.profile:id,user_id,first_name,last_name,image',
                'apartment:id,city,area,image'
            ])
            ->orderByDesc('created_at')
            ->get();

        // تعديل شكل البيانات حسب الدور
        $conversations = $conversations->map(function ($conversation) use ($user_id)
        {
            if ($conversation->owner_id == $user_id)
            {
                // المستخدم الحالي هو المالك
                return [
                    'conversation_id' => $conversation->id,
                    'renter' => [
                        'personal_photo'=> $conversation->renter->profile->personal_photo ?? null,
                        'first_name'=> $conversation->renter->profile->first_name ?? null,
                        'last_name'=> $conversation->renter->profile->last_name ?? null,
                    ],
                    'apartment' => [
                        'city'  => $conversation->apartment->city ?? null,
                        'area'  => $conversation->apartment->area ?? null,
                    ],
                ];
            } else
            {
                // المستخدم الحالي هو المستأجر
                return [
                    'conversation_id' => $conversation->id,
                    'apartment' => [
                        'image' => $conversation->apartment->image ?? null,
                        'city'  => $conversation->apartment->city ?? null,
                        'area'  => $conversation->apartment->area ?? null,
                    ],
                ];
            }
        });

        return response()->json([
            'conversations' => $conversations
        ], 200);
    }
    //____________________________________________________________
    public function getConversation(int $conversationId)// جلب محادثة معينة مع رسائلها
    {
        $conversation = Conversation::with(['messages.sender.profile'])
            ->findOrFail($conversationId);
        // تحقق أن المستخدم جزء من المحادثة
        if (!in_array(Auth::id(), [$conversation->owner_id, $conversation->renter_id]))
        {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // عدل الرسائل بحيث تعرض الدور + الاسم الأول والأخير
        $messages = $conversation->messages->map(function ($message) use ($conversation)
        {
            return
            [
                'id' => $message->id,
                'content' => $message->content,
                'created_at' => $message->created_at,
                'role' => $message->sender_id == $conversation->owner_id ? 'owner' : 'renter',
                'first_name' => $message->sender->profile->first_name ?? null,
                'last_name'  => $message->sender->profile->last_name ?? null,
            ];
        });

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'messages' => $messages,
            ]
        ], 200);
    }
    //____________________________________________________________
    // public function getConversations()// جلب كل المحادثات الخاصة بالمستخدم الحالي
    // {
    //     $user_id = Auth::id();

    //     $conversations = Conversation::where('owner_id', $user_id)
    //         ->orWhere('renter_id', $user_id)
    //         ->with([
    //             'owner.profile:id,user_id,first_name,last_name',
    //             'renter.profile:id,user_id,first_name,last_name'
    //         ])
    //         ->orderByDesc('created_at')
    //         ->get();

    //     return response()->json([
    //         'conversations' => $conversations
    //     ], 200);
    // }
    //____________________________________________________________

        // $conversation = Conversation::with('messages.sender')
        // ->findOrFail($conversationId);
        // // تحقق أن المستخدم جزء من المحادثة
        // if (!in_array(Auth::id(),[$conversation->owner_id,$conversation->renter_id]))
        // {
        //     return response()->json(['message' => 'Unauthorized'],403);
        // }

        // return response()->json([
        //     'conversation :' => $conversation
        // ],200);

    //____________________________________________________________
    // public function destroy($conversationId)// حذف محادثة
    // {
    //     $conversation = Conversation::findOrFail($conversationId);
    //     // فقط المؤجر أو المستأجر يقدر يحذف المحادثة
    //     if (!in_array(Auth::id(),[$conversation->owner_id,$conversation->renter_id]))
    //     {
    //         return response()->json(['message' => 'Unauthorized'], 403);
    //     }
    //     $conversation->delete();

    //     return response()->json([
    //         'message' => 'Conversation deleted successfully'
    //     ],200);
    // }
}
