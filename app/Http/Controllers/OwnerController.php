<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Notifications\NotificationServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OwnerController extends Controller
{

  public function approved(int $reservationId) // الموافقة
    {
        try
        {
            $reservation = Reservation::with('apartment')->findOrFail($reservationId);

            if ($reservation->apartment->user_id !== Auth::id())
            {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // التحقق من وجود تعارض مع حجوزات أخرى تمت الموافقة عليها مسبقًا
            $hasApprovedConflict = Reservation::where('apartment_id', $reservation->apartment_id)
                ->where('id', '!=', $reservationId)//استبعاد الحجز الحالي
                ->where('approv_status_reserv', 'approved')
                ->where('start_date', '<=', $reservation->end_date)
                ->where('end_date', '>=', $reservation->start_date)
                ->exists();
            if ($hasApprovedConflict)
            {
                return response()->json([
                    'message' => 'Conflict with another approved reservation'
                ], 409);
            }

            // الموافقة على الطلب الحالي
            $reservation->update([
                'approv_status_reserv' => 'approved',
                'status_pay'=>'paid'
            ]);
            $apartment=$reservation->apartment;
            $apartment->update(['is_available'=>false]);

            // try//إرسال إشعار للمستأجر
            //     {
            //         $renter_id=$reservation->user_id;
            //         $renter=User::findOrFail($renter_id);
            //         $token_fcm=$renter->profile->token_fcm;
            //         if (!$token_fcm)
            //         {
            //             Log::warning("User $renter_id  his reservation has been approved but has no FCM token.");
            //             return response()->json(['message' => 'user his reservation has been approved, but no token found.']);
            //         }

            //             $messaging = app('firebase.messaging');

            //             $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token',$token_fcm)
            //                 ->withNotification(\Kreait\Firebase\Messaging\Notification::create("Approved your reservation", "Your reservation was approved."));

            //             $response = $messaging->send($message);
            //     } catch (\Exception $e) {
            //         return response()->json(['error' => $e->getMessage()], 500);
            //     }

            // try//إرسال إشعار لمالك الشقة
            //     {
            //         $owner_id=$apartment->user_id;
            //         $owner=User::findOrFail($owner_id);
            //         $renter_name=$reservation->user->profile->first_name;
            //         $token_fcm=$owner->profile->token_fcm;
            //         if (!$token_fcm)
            //         {
            //             Log::warning("User $owner_id  Approved to reservations but has no FCM token.");
            //             return response()->json(['message' => 'user Approved to reservations, but no token found.']);
            //         }

            //             $messaging = app('firebase.messaging');

            //             $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token',$token_fcm)
            //                 ->withNotification(\Kreait\Firebase\Messaging\Notification::create("Approved for reservations", " you Approved to $renter_name reservations."));

            //             $response = $messaging->send($message);
            //     } catch (\Exception $e) {
            //         return response()->json(['error' => $e->getMessage()], 500);
            //     }

            // رفض باقي الطلبات المتعارضة (المعلقة فقط)
            $conflictingReservations = Reservation::where('apartment_id', $reservation->apartment_id)
                ->where('id', '!=', $reservation->id)
                ->where('approv_status_reserv', 'pending') //المعلقة فقط
                ->where('start_date', '<=', $reservation->end_date)
                ->where('end_date', '>=', $reservation->start_date)
                ->get();

            foreach ($conflictingReservations as $conflict)
            {
                $conflict->update(['approv_status_reserv' => 'rejected']);

                // try//إرسال إشعار للمستأجر
                // {
                //     $renter_id=$conflict->user_id;
                //     $renter=User::findOrFail($renter_id);
                //     $token_fcm=$renter->profile->token_fcm;
                //     if (!$token_fcm)
                //     {
                //         Log::warning("User $renter_id  his reservation has been rejected but has no FCM token.");
                //         return response()->json(['message' => 'user his reservation has been rejected, but no token found.']);
                //     }

                //         $messaging = app('firebase.messaging');

                //         $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token',$token_fcm)
                //             ->withNotification(\Kreait\Firebase\Messaging\Notification::create("Reservation rejected", "Your reservation was rejected because another request was approved."));

                //         $response = $messaging->send($message);
                // } catch (\Exception $e) {
                //     return response()->json(['error' => $e->getMessage()], 500);
                // }

            }

            return response()->json([
                'message' => 'Reservation Has Been Approved',
                'Reservation' => $reservation
            ], 200);
        }catch(ModelNotFoundException $e){
            return response()->json([
                'error'=>'the reservation is not found'
            ],404);
        }
    }

    //____________________________________________________
    public function rejected(int $reservationId)//رفض
    {
        try
        {
            $reservation=Reservation::findOrFail($reservationId);

            if($reservation->apartment->user_id !== Auth::id())
            {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $reservation->update(['approv_status_reserv'=>'rejected']);

            // try//إرسال إشعار للمستأجر
            //     {
            //         $renter_id=$reservation->user_id;
            //         $renter=User::findOrFail($renter_id);
            //         $token_fcm=$renter->profile->token_fcm;
            //         if (!$token_fcm)
            //         {
            //             Log::warning("User $renter_id  his reservation has been rejected but has no FCM token.");
            //             return response()->json(['message' => 'user his reservation has been rejected, but no token found.']);
            //         }

            //             $messaging = app('firebase.messaging');

            //             $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token',$token_fcm)
            //                 ->withNotification(\Kreait\Firebase\Messaging\Notification::create("Rejected your reservation", "Your reservation was rejected."));

            //             $response = $messaging->send($message);
            //     } catch (\Exception $e) {
            //         return response()->json(['error' => $e->getMessage()], 500);
            //     }

            // try//إرسال إشعار لمالك الشقة
            //     {
            //         $owner_id=Auth::id();
            //         $owner=User::findOrFail($owner_id);
            //         $renter_name=$reservation->user->profile->first_name;
            //         $token_fcm=$owner->profile->token_fcm;
            //         if (!$token_fcm)
            //         {
            //             Log::warning("User $owner_id  rejected to reservations but has no FCM token.");
            //             return response()->json(['message' => 'user rejected to reservations, but no token found.']);
            //         }

            //             $messaging = app('firebase.messaging');

            //             $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token',$token_fcm)
            //                 ->withNotification(\Kreait\Firebase\Messaging\Notification::create("rejected for reservations", " you rejected to $renter_name reservations."));

            //             $response = $messaging->send($message);
            //     } catch (\Exception $e) {
            //         return response()->json(['error' => $e->getMessage()], 500);
            //     }

            return response()->json([
                'message'=>'Reservation Has Been Rejected',
                'Reservation:'=>$reservation
                ],200);
        }catch(ModelNotFoundException $e){
            return response()->json([
                'error'=>'the reservation is not found'
            ],404);
        }
    }

    //____________________________________________________
    public function getAllApartmentsICAR()//عرض كل الشقق لهذا المؤجر مع التفاصيل الخارجية
    {
        $user_id=Auth::user()->id;
        $apartments = Apartment::where('user_id',$user_id)
        ->orderByDesc('created_at')
        ->get()
        ->map(function($apartment)
        {
            return
            [
                'id'             => $apartment->id,
                'image'          => $apartment->image,
                'city'           => $apartment->city,
                'area'           => $apartment->area,
                'address'           => $apartment->address,
                'space'         => $apartment->space,
                'average_rating'=> round($apartment->ratings()->avg('rating_value'), 2),
                'price'=> $apartment->price,
                'room'=> $apartment->room,
                'bath_room'=>$apartment->bath_room,
                'is_available'  => $apartment->is_available,
            ];
        });

        return response()->json([
            'message'    => 'Apartments with City, Area, Image, and Average Rating:',
            'apartments' => $apartments
        ], 200);
    }
    //____________________________________________________
    public function getApartmentWithAllDetailed(int $apartmentId)//عرض شقة معينة مع كل التفاصيل
    {
        try
        {
            $apartment = Apartment::with(['ratings.user'])->findOrFail($apartmentId);
             $data =
             [
                'id'            => $apartment->id,
                'image'          => $apartment->image,
                'city'          => $apartment->city,
                'area'          => $apartment->area,
                'space'         => $apartment->space,
                'address'          => $apartment->address,
                'room'=> $apartment->room,
                'bath_room'=>$apartment->bath_room,
                'price'         => $apartment->price,
                'is_available'  => $apartment->is_available,
            ];

            return response()->json([
                'apartments'=>$data
            ], 200);
        }catch(ModelNotFoundException $e){
            return response()->json([
                'error'=>'the apartment is not found'
            ],404);
        }
    }
    //____________________________________________________
    public function pendingReservation()//الحجوزات المعلقة للموافقة
    {
        $reservations = Reservation::whereHas('apartment',function($query)
        {
            $query->where('user_id', Auth::id()); // الشقق اللي يملكها المستخدم الحالي
        })
            ->where('approv_status_reserv','pending')
            ->with('apartment','user.profile')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
                'pending reservations'=>$reservations
            ], 200);
    }
    //____________________________________________________
    public function approvedReservation()//الحجوزات الموافق عليهم
    {
        $reservations = Reservation::whereHas('apartment',function($query)
        {
            $query->where('user_id', Auth::id()); // الشقق اللي يملكها المستخدم الحالي
        })
            ->where('approv_status_reserv','approved')
            ->with('apartment','user.profile')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
                'approved reservations'=>$reservations
            ], 200);
    }
    //____________________________________________________
    public function updateStatus_pay(int $reservationId)//تحديث حالة الدفع
    {
        try
       {
            $user_id=Auth::user()->id;
            $reservation=Reservation::findOrFail($reservationId);

            if($reservation->apartment->user_id !== $user_id)
            {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $reservation->update(['status_pay'=>'paid']);

            try//إرسال إشعار للمستأجر
            {
                $renter_id=$reservation->user_id;
                $renter=User::findOrFail($renter_id);
                $token_fcm=$renter->profile->token_fcm;
                if (!$token_fcm)
                {
                    Log::warning("User $renter_id  his reservation has been paid but has no FCM token.");
                    return response()->json(['message' => 'user his reservation has been paid, but no token found.']);
                }

                    $messaging = app('firebase.messaging');

                    $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token',$token_fcm)
                        ->withNotification(\Kreait\Firebase\Messaging\Notification::create("Paid your reservation", "Your reservation was paid."));

                    $response = $messaging->send($message);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }

        // try//إرسال إشعار لمالك الشقة
        //     {
        //         $owner_id=Auth::id();
        //         $owner=User::findOrFail($owner_id);
        //         $renter_name=$reservation->user->profile->first_name;
        //         $token_fcm=$owner->profile->token_fcm;
        //         if (!$token_fcm)
        //         {
        //             Log::warning("User $owner_id  updated status_pay for paid but has no FCM token.");
        //             return response()->json(['message' => 'user updated status_pay for paid, but no token found.']);
        //         }

        //             $messaging = app('firebase.messaging');

        //             $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token',$token_fcm)
        //                 ->withNotification(\Kreait\Firebase\Messaging\Notification::create("Paid this reservation", " you updated status_pay for paid $renter_name reservations."));

        //             $response = $messaging->send($message);
        //     } catch (\Exception $e) {
        //         return response()->json(['error' => $e->getMessage()], 500);
        //     }

            return response()->json([
                'message'=>'Updated status_pay to paid',
                'Reservation:'=>$reservation
                ],200);
        }catch(ModelNotFoundException $e){
            return response()->json([
                'error'=>'the reservation is not found'
            ],404);
      }
    }
    //____________________________________________________
    public function countApartmentOwner()//عدد شقق المستخدم
    {
        $user_id=Auth::user()->id;
        $count_apartments=Apartment::where('user_id',$user_id)->count();
        return response()->json([
            'message'=>'Number of Your Apartments :',
            'count'=>$count_apartments
        ],200);
    }

}
