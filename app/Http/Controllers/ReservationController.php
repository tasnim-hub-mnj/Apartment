<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationRequest;
use App\Models\Apartment;
use App\Models\Reservation;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;


class ReservationController extends Controller
{
    public function store(StoreReservationRequest $request,int $apartmentId)//اضافة
    {
        try
        {
            $apartment = Apartment::findOrFail($apartmentId);

            if (Reservation::hasConflictWithApproved(
                $apartmentId,
                $request->start_date,
                $request->end_date,
                null,
                ))
            {
                return response()->json([
                    'message' => 'Apartment already reserved in this period',
                    'detail'  => 'Or you already have a reservation for this apartment in this period'//الشقة محجوزة بالفعل في هذه الفترة
                ], 409);

                    // try//إرسال إشعار للمستأجر
                    // {
                    //     $renter_id=Auth::id();
                    //     $renter=User::findOrFail($renter_id);
                    //     $token_fcm=$renter->profile->token_fcm;
                    //     if (!$token_fcm)
                    //     {
                    //         Log::warning("User $renter_id  his reservation Conflict but has no FCM token.");
                    //         return response()->json(['message' => 'reservation user Conflict, but no token found.']);
                    //     }

                    //         $messaging = app('firebase.messaging');

                    //         $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token',$token_fcm)
                    //             ->withNotification(\Kreait\Firebase\Messaging\Notification::create("You Reservation Conflict", "Your reservation was Conflict ."));

                    //         $response = $messaging->send($message);
                    // } catch (\Exception $e) {
                    //     return response()->json(['error' => $e->getMessage()], 500);
                    // }
            }

            $days = Carbon::parse($request->start_date)
                ->diffInDays(Carbon::parse($request->end_date)) + 1;//عدد الايام

            $amount = $days * $apartment->price;

            $reservation = Reservation::create(
            [
                'user_id'       => Auth::id(),
                'apartment_id'  => $apartmentId,
                'start_date'    => $request->start_date,
                'end_date'      => $request->end_date,
                'required_amount' => $amount,
                'status'        => 'confirmed',
                'approv_status_reserv' => 'pending',
                'status_pay'    => 'unpaid'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Apartment not found'], 404);
        }

        // try//إرسال إشعار لمالك الشقة
        //     {
        //         $owner_id=$apartment->user_id;
        //         $owner=User::findOrFail($owner_id);
        //         $token_fcm=$owner->profile->token_fcm;
        //         if (!$token_fcm)
        //         {
        //             Log::warning("User $owner_id  his apartment requested for reservations but has no FCM token.");
        //             return response()->json(['message' => 'apartment user requested for reservations, but no token found.']);
        //         }

        //             $messaging = app('firebase.messaging');

        //             $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token',$token_fcm)
        //                 ->withNotification(\Kreait\Firebase\Messaging\Notification::create("Apartment requested for reservations", "Your apartment was requested for reservations."));

        //             $response = $messaging->send($message);
        //     } catch (\Exception $e) {
        //         return response()->json(['error' => $e->getMessage()], 500);
        //     }

        //     try//إرسال إشعار للمستأجر
        //     {
        //         $renter_id=$reservation->user_id;
        //         $renter=User::findOrFail($renter_id);
        //         $token_fcm=$renter->profile->token_fcm;
        //         if (!$token_fcm)
        //         {
        //             Log::warning("User $renter_id  his reservation pending but has no FCM token.");
        //             return response()->json(['message' => 'reservation user pending, but no token found.']);
        //         }

        //             $messaging = app('firebase.messaging');

        //             $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token',$token_fcm)
        //                 ->withNotification(\Kreait\Firebase\Messaging\Notification::create("You Reservation Pending", "Your reservation was Pending ."));

        //             $response = $messaging->send($message);
        //     } catch (\Exception $e) {
        //         return response()->json(['error' => $e->getMessage()], 500);
        //     }

        return response()->json([
            'message'=>'Successfully Create Reservation :',
            'Reservation'=>$reservation
        ], 201);
    }
    //____________________________________________________
    public function update(UpdateReservationRequest $request,int $reservationId)//تعديل
    {
        try
        {
            $reservation = Reservation::with('apartment')->findOrFail($reservationId);

            if($reservation->user_id != Auth::id())
            {
                return response()->json(['message' => 'Unauthorized'],403);
            }

            if(Reservation::hasConflictWithApproved(//تحقق من وجود تضارب مع حجوزات أخرى لنفس الشقة
                $reservation->apartment_id,
                $request->start_date,
                $request->end_date,
                $reservation->id,
            ))
            {
                return response()->json([
                    'message' => 'This reservation conflicts with another existing reservation for this apartment'
                ], 409);
            }

            $apartment=$reservation->apartment;
            $days = Carbon::parse($request->start_date)
                ->diffInDays(Carbon::parse($request->end_date)) + 1;
            $amount = $days * $apartment->price;

            $reservation->update(
            [
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'approv_status_reserv' => 'pending',
                'required_amount' => $amount,
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Reservation not found'], 404);
        }
        // try//إرسال إشعار لمالك الشقة
        //     {
        //         $owner_id=$apartment->user_id;
        //         $owner=User::findOrFail($owner_id);
        //         $renter_name=Auth::user()->profile->first_name;
        //         $token_fcm=$owner->profile->token_fcm;
        //         if (!$token_fcm)
        //         {
        //             Log::warning("User $owner_id  his apartment requested for reservations but has no FCM token.");
        //             return response()->json(['message' => 'apartment user requested for reservations, but no token found.']);
        //         }

        //             $messaging = app('firebase.messaging');

        //             $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token',$token_fcm)
        //                 ->withNotification(\Kreait\Firebase\Messaging\Notification::create("Apartment requested for reservations", "$renter_name updated to reserve your apartment."));

        //             $response = $messaging->send($message);
        //     } catch (\Exception $e) {
        //         return response()->json(['error' => $e->getMessage()], 500);
        //     }

        //     try//إرسال إشعار للمستأجر
        //     {
        //         $renter_id=$reservation->user_id;
        //         $renter=User::findOrFail($renter_id);
        //         $token_fcm=$renter->profile->token_fcm;
        //         if (!$token_fcm)
        //         {
        //             Log::warning("User $renter_id  his reservation pending but has no FCM token.");
        //             return response()->json(['message' => 'reservation user pending, but no token found.']);
        //         }

        //             $messaging = app('firebase.messaging');

        //             $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token',$token_fcm)
        //                 ->withNotification(\Kreait\Firebase\Messaging\Notification::create("You Reservation Pending", "Your reservation was Pending ."));

        //             $response = $messaging->send($message);
        //     } catch (\Exception $e) {
        //         return response()->json(['error' => $e->getMessage()], 500);
        //     }

        return response()->json([
            'message'=>'Successfully Update Reservation :',
            'Reservation'=>$reservation

        ], 200);
    }
    //____________________________________________________
    public function cancellation(int $reservationId)//الغاء
    {
        try
        {
            $user_id=Auth::user()->id;
            $reservation=Reservation::findOrFail($reservationId);
            if($reservation->user_id != $user_id)
                return response()->json(['message'=>'Unauthorized'],403);

            if($reservation->approv_status_reserv === 'approved')
            {
                return response()->json([
                'message'=>'You can not Canceled Reservation,because it is already approved',
                ],403);
            }

            $reservation->update(['status'=>'cancelled']);
            $reservation->update(['approv_status_reserv'=>'rejected']);

            return response()->json([
                'message'=>'Canceled Reservation',
                'Reservation:'=>$reservation
                ],200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message'=>'Reservation not found'],404);
        }
    }
    //____________________________________________________
    public function getConfirmedReservations()//عرض الحجوزات المؤكدة لهذا المستخدم مع الشقة
    {
        $user_id=Auth::user()->id;
        $reservations=Reservation::where('user_id',$user_id)
        ->where('status','confirmed')
        ->orderByDesc('created_at')
        ->with('apartment')
        ->get();

        return response()->json([
                'message'=>'Confirm Reservations',
                'Reservations:'=>$reservations
                ],200);
    }
    //____________________________________________________
    public function getCancelledReservations()//عرض الحجوزات الملغاة لهذا المستخدم مع الشقة
    {
        $user_id=Auth::user()->id;
        $reservations=Reservation::where('user_id',$user_id)
        ->where('status','cancelled')
        ->orderByDesc('created_at')
        ->with('apartment')
        ->get();

        return response()->json([
                'message'=>'Cancell Reservations',
                'Reservations:'=>$reservations
                ],200);
    }
    //____________________________________________________
    public function getFinishedReservations()//عرض الحجوزات المنتهية لهذا المستخدم مع الشقة
    {
        $user_id=Auth::user()->id;
        $reservations=Reservation::where('user_id',$user_id)
        ->where('status','finished')
        ->with('apartment')
        ->orderByDesc('created_at')
        ->get();

        return response()->json([
                'message'=>'Finish Reservations',
                'Reservations:'=>$reservations
                ],200);
    }
    //____________________________________________________
    public function autoFinishReservations()//تحديث الحجوزات المنتهية تلقائيًا
    {
        // جيب كل الحجوزات اللي حالتها "approved" ولسا ما خلصت
        $reservations = Reservation::where('approv_status_reserv','approved')
            ->where('status','!=','finished')
            ->get();

        $updated = [];

        foreach ($reservations as $reservation)
        {
            $endDate = Carbon::parse($reservation->end_date);
            // إذا تاريخ النهاية مرّ
            if (Carbon::now()->gt($endDate))
            {
                $apartment=$reservation->apartment;
                $reservation->update(['status' => 'finished']);
                $apartment->update(['is_available'=>true]);
                $updated[] = $reservation->id;
            }

            // try//إرسال إشعار للمستأجر
            // {
            //     $renter_id=$reservation->user_id;
            //     $renter=User::findOrFail($renter_id);
            //     $token_fcm=$renter->profile->token_fcm;
            //     if (!$token_fcm)
            //     {
            //         Log::warning("User $renter_id  his reservation finished but has no FCM token.");
            //         return response()->json(['message' => 'reservation user finished, but no token found.']);
            //     }

            //         $messaging = app('firebase.messaging');

            //         $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token',$token_fcm)
            //             ->withNotification(\Kreait\Firebase\Messaging\Notification::create("You Reservation finished", "Your reservation was finished."));

            //         $response = $messaging->send($message);
            // } catch (\Exception $e) {
            //     return response()->json(['error' => $e->getMessage()], 500);
            // }
        }

        return response()->json([
            'message' => 'Finished reservations updated',
            'updated_reservations' => $updated
        ], 200);
    }
    //____________________________________________________
    public function getPendingReservations()//عرض الحجوزات المعلقة لهذا المستخدم مع الشقة
    {
        $user_id=Auth::user()->id;
        $reservations=Reservation::where('user_id',$user_id)
            ->where('approv_status_reserv','pending')
            ->with('apartment')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
                'message'=>'Pending Reservations',
                'Reservations:'=>$reservations
                ],200);
    }
    //____________________________________________________
    public function getApprovedReservations()//عرض الحجوزات الموافق عليها لهذا المستخدم مع الشقة
    {
        $user_id=Auth::user()->id;
        $reservations=Reservation::where('user_id',$user_id)
            ->where('approv_status_reserv','approved')
            ->with('apartment')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
                'message'=>'Approve Reservations',
                'Reservations:'=>$reservations
                ],200);
    }

}
