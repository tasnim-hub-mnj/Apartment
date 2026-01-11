<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateApartmentRequest;
use App\Http\Requests\UpdateReservationRequest;
use App\Models\Apartment;
use App\Models\Payment;
use App\Models\Reservation;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;


class ReservationController extends Controller
{
    public function store(StoreReservationRequest $request,int $apartmentId)//اضافة
    {
        $apartment = Apartment::findOrFail($apartmentId);

        if (Reservation::hasConflict(
            $apartmentId,
            $request->start_date,
            $request->end_date,
            null,
            Auth::id()
            ))
        {
            return response()->json([
                'message' => 'Apartment already reserved in this period',
                'detail'  => 'Or you already have a reservation for this apartment in this period'//الشقة محجوزة بالفعل في هذه الفترة
            ], 409);
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
            'pay_method'        => $request->pay_method,
            'card_number'      => $request->card_number,
            'status'        => 'confirmed',
            'approv_status_reserv' => 'pending',
            'status_pay'    => 'unpaid'
        ]);

        NotificationService::send(//ارسال اشعار لمالك الشقة
        $apartment->user_id,
        'New reservation request',
        'Someone requested to reserve your apartment'
         );

        return response()->json([
            'message'=>'Successfully Create Reservation :',
            'Reservation'=>$reservation
        ], 201);
    }

    //____________________________________________________
    public function update(UpdateReservationRequest $request,int $reservationId)//تعديل
    {
        $reservation = Reservation::with('apartment')->findOrFail($reservationId);

        if($reservation->user_id != Auth::id())
        {
            return response()->json(['message' => 'Unauthorized'],403);
        }

        if(Reservation::hasConflict(//تحقق من وجود تضارب مع حجوزات أخرى لنفس الشقة
            $reservation->apartment_id,
            $request->start_date,
            $request->end_date,
            $reservation->id,
            Auth::id()
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
            'end_date'   => $request->end_date,
            'required_amount' => $amount,
            'status'        => 'confirmed',
            'approv_status_reserv' => 'pending',
            'pay_method'        => $request->pay_method,
            'card_number'      => $request->card_number,
        ]);

        NotificationService::send(//ارسال اشعار لمالك الشقة
        $apartment->user_id,
        'New reservation request',
        'Someone updated to reserve your apartment'//هناك احد قام بتحديث حجز شقتك
        );

        return response()->json([
            'message'=>'Successfully Update Reservation :',
            'Reservation'=>$reservation
        ], 200);
    }
    //____________________________________________________
    public function cancellation(int $reservationId)//الغاء
    {
            $user_id=Auth::user()->id;
            $reservation=Reservation::findOrFail($reservationId);
            if($reservation->user_id != $user_id)
                return response()->json(['message'=>'Unauthorized'],403);
            $reservation->update(['status'=>'cancelled']);
            $reservation->update(['approv_status_reserv'=>'rejected']);//---------------

            return response()->json([
                'message'=>'Canceled Reservation',
                'Reservation:'=>$reservation
                ],200);
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

        return response()->json($reservations,200);
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

        return response()->json($reservations,200);
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

        return response()->json($reservations,200);
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

        return response()->json($reservations,200);
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

        return response()->json($reservations,200);
    }
    //____________________________________________________
    public function updatePay(UpdateReservationRequest $request,int $reservationId)
    {
        $reservation = Reservation::findOrFail($reservationId);
        if($reservation->user_id !== Auth::id())
        {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $reservation->update([
            'pay_method' => $request->pay_method,
            'card_number' => $request->card_number,
            // 'status'=>'finished',
            // 'status_pay' => 'paid'
        ]);

        return response()->json(['message' => 'Payment completed']);
    }
    //____________________________________________________
    public function autoFinishReservations()
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
        }

        return response()->json([
            'message' => 'Finished reservations updated',
            'updated_reservations' => $updated
        ], 200);
    }
    //____________________________________________________
    // public function getAllReservation()//عرض كل الحجوزات لهذا المستخدم مع الشقة
    // {
    //     $user_id=Auth::user()->id;
    //     $reservations=Reservation::where('user_id',$user_id)->with('apartment')->get();
    //     return response()->json($reservations,200);
    // }
    //____________________________________________________
    // public function setReservationFinishedAouto($reservationId)//تحديث حالة الحجز الى منتهي تلقائيا
    // {
    //     $reservation = Reservation::with('apartment')->findOrFail($reservationId);
    //     $apartment=$reservation->apartment;
    //     if(Carbon::now()->gt($reservation->end_date)&&$reservation->approv_status_reserv==='approved')//انتهى الحجز
    //     {
    //         $reservation->update(['status'=>'finished']);
    //     }

    //     return response()->json([
    //         'message'=>'Reservation status updated to finished if applicable',
    //         'Reservation:'=>$reservation,
    //         'Apartment:'=>$apartment
    //     ],200);

    // }
    //____________________________________________________
    // public function getReservationsStatus()//حالة الحجز
    // {
    //     $reservation=Auth::user()->reservations()
    //             ->orderByRaw("FIELD(status,'confirmed','cancelled')")
    //             ->get();
    //     return response()->json($reservation,200);
    // }
    //____________________________________________________
    // public function show(int $reservationId)//طباعة صف (حجز)
    // {
    //    try
    //   {
    //    $user_id=Auth::user()->id;
    //    $reservation=Reservation::findOrFail($reservationId);
    //    $apartment=$reservation->apartment;
    //    if($reservation->user_id != $user_id)
    //         return response()->json(['message'=>'Unauthorized'],403);
    //    return response()->json([
    //         'Reservation:'=>$reservation,
    //         'Apartment:'=>$apartment
    //         ],200);
    //   }catch(ModelNotFoundException $e){
    //         return response()->json([
    //             'error'=>'the reservation is not found'
    //         ],404);
    //   }
    // }
    //____________________________________________________
    // public function getReservationsUser(int $reservationId)//طباعة صاحب الحجز
    // {
    //         $reservation = Reservation::findOrFail($reservationId);
    //         $user = $reservation->user;
    //         return response()->json($user, 200);
    // }
    //____________________________________________________
    //  public function store(StoreReservationRequest $request,$apartmentId)//اضافة
    // {

    //     $user_id=Auth::user()->id;
    //     $apartment=Apartment::findOrFail($apartmentId);
    //     $amount=$apartment->price;
    //     $validatedData=$request->validated();
    //     $validatedData['user_id']=$user_id;
    //     $validatedData['apartment_id']=$apartmentId;
    //     $validatedData['required_amount']=$amount;
    //     $reservation=Reservation::create($validatedData);
    //     $reservation_approved=$reservation->approv_status_reserv;
    //     if(Carbon::now()->lt($reservation->end_date)&&$reservation_approved === 'approved')//لم ينتهي الحجز
    //     {
    //         $apartment->update(['is_available'=>false]);
    //     }
    //     else
    //     {
    //         $apartment->update(['is_available'=>true]);
    //         $reservation->update(['status'=>'finished']);

    //     }
    //     return response()->json([
    //         'message'=>'Successfully Reservation ^-^',
    //         'Reservation:'=>$reservation,
    //         'Apartment:'=>$apartment

    //     ],201);

    // }

    //____________________________________________________
    //     public function update(UpdateReservationRequest $request, int $reservationId)//تعديل
// {
//     $reservation = Reservation::with('apartment')->findOrFail($reservationId);

//     // تحقق من أن المستخدم هو صاحب الحجز
//     if ($reservation->user_id != Auth::id()) {
//         return response()->json(['message' => 'Unauthorized'], 403);
//     }

//     // تحقق من وجود تضارب مع حجوزات أخرى لنفس الشقة
//     if (Reservation::hasConflict(
//         $reservation->apartment_id,
//         $request->start_date,
//         $request->end_date,
//         $reservation->id // استثناء الحجز الحالي
//     )) {
//         return response()->json([
//             'message' => 'Conflict with another reservation'
//         ], 409);
//     }

//     $apartment = $reservation->apartment;

//     // حساب عدد الأيام
//     $days = Carbon::parse($request->start_date)
//         ->diffInDays(Carbon::parse($request->end_date)) + 1;

//     // حساب المبلغ المطلوب
//     $amount = $days * $apartment->price;
//     dd($request->all());

//     // تحديث بيانات الحجز
//     $reservation->update([
//         'start_date'          => $request->start_date,
//         'end_date'            => $request->end_date,
//         'required_amount'     => $amount,
//         'status'              => 'confirmed',
//         'approv_status_reserv'=> 'pending',
//         'pay_method'          => $request->pay_method,
//         'card_number'         => $request->card_number,
//     ]);

//     // إرسال إشعار لمالك الشقة
//     NotificationService::send(
//         $apartment->user_id,
//         'New reservation request',
//         'Someone updated to reserve your apartment'
//     );

//     return response()->json([
//         'message'     => 'Reservation updated successfully',
//         'reservation' => $reservation
//     ], 200);
// }
    //____________________________________________________
}
