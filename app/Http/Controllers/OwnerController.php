<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\Reservation;
use Carbon\Carbon;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Notifications\NotificationServiceProvider;
use Illuminate\Support\Facades\Auth;

class OwnerController extends Controller
{

  public function approved(int $reservationId) // الموافقة
    {
        $reservation = Reservation::with('apartment')->findOrFail($reservationId);

        if ($reservation->apartment->user_id !== Auth::id())
        {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        // 2. التحقق من وجود تعارض مع حجوزات أخرى تمت الموافقة عليها مسبقًا
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
        // 3. الموافقة على الطلب الحالي
        $reservation->update([
            'approv_status_reserv' => 'approved',
        ]);

        $apartment=$reservation->apartment;
        $apartment->update(['is_available'=>false]);

        NotificationService::send(
            $reservation->user_id,
            'Reservation approved',
            'Your reservation has been approved'
        );

        // 4. رفض باقي الطلبات المتعارضة (المعلقة فقط)
        $conflictingReservations = Reservation::where('apartment_id', $reservation->apartment_id)
            ->where('id', '!=', $reservation->id)
            ->where('approv_status_reserv', 'pending') //المعلقة فقط
            ->where('start_date', '<=', $reservation->end_date)
            ->where('end_date', '>=', $reservation->start_date)
            ->get();

        foreach ($conflictingReservations as $conflict)
        {
            $conflict->update(['approv_status_reserv' => 'rejected']);

            NotificationService::send(
                $conflict->user_id,
                'Reservation rejected',
                'Your reservation was rejected because another request was approved'
            );
        }

        // 5. إرجاع الرد النهائي
        return response()->json([
            'message' => 'Reservation Has Been Approved',
            'Reservation' => $reservation
        ], 200);
    }

    //____________________________________________________
    public function rejected(int $reservationId)//رفض
    {
        // $user_id=Auth::user()->id;
        $reservation=Reservation::findOrFail($reservationId);

        if($reservation->apartment->user_id !== Auth::id())
        {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $reservation->update(['approv_status_reserv'=>'rejected']);

        NotificationService::send(//إرسال إشعار الرفض
            $reservation->user_id,
            'Reservation rejected',
            'Your reservation has been rejected'
        );

        return response()->json([
            'message'=>'Reservation Has Been Rejected',
            'Reservation:'=>$reservation
            ],200);
      }

    //____________________________________________________
    public function getAllApartmentsICAR()//عرض كل الشقق لهذا المستخدم مع المدينة والمنطقة والتقييم المتوسط و الصورة
    {
        $user_id=Auth::user()->id;
        $apartments = Apartment::where('user_id',$user_id)
        ->withAvg('ratings', 'rating_value')
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
    public function getApartmentWithAllDetailed(int $apartmentId)//عرض شقة معينة مع التقييمات والمتوسط
    {
        try
        {
            $apartment = Apartment::with(['ratings.user'])->findOrFail($apartmentId);
             $data = [
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

            return response()->json(['apartments'=>$data], 200);
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

        return response()->json($reservations, 200);
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

        return response()->json($reservations,200);
    }
    //____________________________________________________
    public function updateStatus_pay(int $reservationId)//تحديث حالة الدفع
    {
        try
       {
            $user_id=Auth::user()->id;
            $reservation=Reservation::findOrFail($reservationId);

            if($reservation->apartment->user_id !== Auth::id())
            {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $reservation->update(['status_pay'=>'paid']);

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
    //____________________________________________________
    // public function getReservationsApprov_status_reserv()//حالة موافقة الحجز
    // {
    //     $reservations = Auth::user()->reservations()
    //             ->orderByRaw("FIELD(approv_status_reserv,'pending','approved','rejected')")
    //             ->get();
    //     return response()->json($reservations,200);
    // }
        //____________________________________________________

    // public function getAllApartmentsWithAllDetailed()//عرض كل الشقق لهذا المستخدم مع كل التفاصيل
    // {
    //     // $user_id=Auth::user()->id;
    //     $apartments=Auth::user()->apartments()->with('city','area','ratings','reservations')->get();
    //     $detailed_apartments=$apartments->map(function($apartment)
    //     {
    //         $apartment_data=$apartment->toArray();
    //         $apartment_data['average_rating']=$apartment->ratings()->avg('rating_value');
    //         return [
    //             // 'id'=>$apartment_data['id'],
    //             'city'=>$apartment_data['city']['name'],
    //             'area'=>$apartment_data['area']['name'],
    //             'average_rating'=>$apartment_data['average_rating'],
    //             'space'=>$apartment_data['space'],
    //             'size'=>$apartment_data['size'],
    //             'room'=> $apartment->room,
                // 'bath_room'=>$apartment->bath_room,
    //             'price'=>$apartment_data['price'],
    //             'is_available'=>$apartment_data['is_available'],
    //         ];
    //     });
    //     return response()->json([
    //         'message'=>'Detailed Apartment :',
    //         $detailed_apartments
    //         ],200);
    // }

}
