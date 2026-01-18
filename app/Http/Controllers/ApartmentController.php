<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApartmentRequest;
use App\Http\Requests\StoreRatingRequest;
use App\Http\Requests\UpdateApartmentRequest;
use App\Models\Apartment;
use App\Models\Rating;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;


class ApartmentController extends Controller
{
    public function store(StoreApartmentRequest $request)//اضافة
    {
        $user_id=Auth::user()->id;
        $validatedData=$request->validated();
        $validatedData['user_id']=$user_id;
        if($request->hasFile('image'))
        {
            $path=$request->file('image')->store('apartment','public');
            $validatedData['image']=$path;
        }
        $apartment=Apartment::create($validatedData);

        // // ارسال اشعار لمالك الشقة
        // try
        // {
        //     $owner_id=$user_id;
        //     $owner=User::findOrFail($owner_id);
        //     $token_fcm=$owner->profile->token_fcm;
        //     if (!$token_fcm)
        //     {
        //         Log::warning("User $owner_id  his apartment stored but has no FCM token.");
        //         return response()->json(['message' => 'apartment user stored, but no token found.']);
        //     }

        //     $messaging = app('firebase.messaging');

        //     $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token',$token_fcm)
        //         ->withNotification(\Kreait\Firebase\Messaging\Notification::create("Apartment Stored", "Your apartment was stored."));

        //     $response = $messaging->send($message);
        // } catch (\Exception $e) {
        //     return response()->json(['error' => $e->getMessage()], 500);
        // }
        return response()->json([
            'message' => 'Apartment Created successfully',
            'apartment' => $apartment
        ], 201);
    }
    //____________________________________________________
    public function update(UpdateApartmentRequest $request,$apartmentId)//تعديل
    {
        try
        {
            $user_id=Auth::user()->id;
            $apartment=Apartment::findOrFail($apartmentId);

            if($apartment->user_id != $user_id)
            {
                return response()->json(['message'=>'Unauthorized'],403);
            }
            $data = $request->validated();

            if($request->hasFile('image'))
            {
                if ($apartment->image)
                {
                    Storage::disk('public')->delete($apartment->image);
                }
                $path=$request->file('image')->store('apartment','public');
                $data['image'] = $path;
            }
            $apartment->update($data);

            // // ارسال اشعار لمالك الشقة
            // try
            //     {
            //         $owner_id=$user_id;
            //         $owner=User::findOrFail($owner_id);
            //         $token_fcm=$owner->profile->token_fcm;
            //         if (!$token_fcm)
            //         {
            //             Log::warning("User $owner_id  his apartment updated but has no FCM token.");
            //             return response()->json(['message' => 'apartment user updated, but no token found.']);
            //         }

            //             $messaging = app('firebase.messaging');

            //             $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token',$token_fcm)
            //                 ->withNotification(\Kreait\Firebase\Messaging\Notification::create("Apartment Updated", "Your apartment was updated."));

            //             $response = $messaging->send($message);
            //     } catch (\Exception $e) {
            //         return response()->json(['error' => $e->getMessage()], 500);
            //     }
            return response()->json([
                'message' => 'Apartment Updated successfully',
                'apartment' => $apartment
            ], 200);
        }catch(ModelNotFoundException $e){
            return response()->json([
                'error'=>'the apartment is not found'
            ],404);
        }
    }
    //____________________________________________________
    public function destroy(int $apartmentId)//حذف
    {
      try
      {
        $apartment = Apartment::with('reservations')->findOrFail($apartmentId);

        if ($apartment->user_id != Auth::id())
        {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        //تحقق من وجود حجوزات غير منتهية وموافق عليها
        $hasActiveApprovedReservations = $apartment->reservations()
            ->where('status', '!=', 'finished')           // غير منتهية
            ->where('approv_status_reserv', 'approved')  // موافق عليها
            ->exists();
        if ($hasActiveApprovedReservations)
        {
            return response()->json([
                'message' => 'Cannot delete apartment because it has active approved reservations'
            ], 409);
        }
        //رفض جميع الطلبات المعلقة المرتبطة بالشقة قبل الحذف
        $pendingReservations = $apartment->reservations()
            ->where('approv_status_reserv', 'pending')
            ->get();
        foreach ($pendingReservations as $reservation)
        {
            $reservation->update(['approv_status_reserv' => 'rejected']);
            // try//إرسال إشعار لمستخدم الحجز المرفوض
            // {
            //     $renter_id=$reservation->user_id;
            //     $renter=User::findOrFail($renter_id);
            //     $token_fcm=$renter->profile->token_fcm;
            //     if (!$token_fcm)
            //     {
            //         Log::warning("User $renter_id  his reservation rejected but has no FCM token.");
            //         return response()->json(['message' => 'reservation user rejected, but no token found.']);
            //     }

            //         $messaging = app('firebase.messaging');

            //         $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token',$token_fcm)
            //             ->withNotification(\Kreait\Firebase\Messaging\Notification::create("You Reservation rejected", "Your reservation was rejected because the apartment was deleted."));

            //         $response = $messaging->send($message);
            // } catch (\Exception $e) {
            //     return response()->json(['error' => $e->getMessage()], 500);
            // }
        }

        $apartment->delete();
        // try//إرسال إشعار لمالك الشقة
        //     {
        //         $owner_id=Auth::id();
        //         $owner=User::findOrFail($owner_id);
        //         $token_fcm=$owner->profile->token_fcm;
        //         if (!$token_fcm)
        //         {
        //             Log::warning("User $owner_id  his apartment deleted but has no FCM token.");
        //             return response()->json(['message' => 'apartment user deleted, but no token found.']);
        //         }

        //             $messaging = app('firebase.messaging');

        //             $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token',$token_fcm)
        //                 ->withNotification(\Kreait\Firebase\Messaging\Notification::create("Apartment deleted", "Your apartment was deleted."));

        //             $response = $messaging->send($message);
        //     } catch (\Exception $e) {
        //         return response()->json(['error' => $e->getMessage()], 500);
        //     }

        return response()->json([
            'message' => 'Apartment deleted successfully'
        ], 200);
      }catch(ModelNotFoundException $e){
            return response()->json([
                'error'=>'the apartment is not found'
            ],404);
      }
    }
    //____________________________________________________
    public function addRating(StoreRatingRequest $request,$apartmentId)//اضافة تقييم
    {
        try
        {
            $user_id=Auth::user()->id;
            $validatedData=$request->validated();
            $validatedData['user_id']=$user_id;
            $validatedData['apartment_id']=$apartmentId;
            $apartment = Apartment::findOrFail($apartmentId);
            $reservation = $apartment->reservations()
                        ->where('user_id', $user_id)
                        ->where('status', 'finished')
                        ->first();
            if ($reservation)
            {
                $rating = Rating::create($validatedData);

                return response()->json([
                    'message' => 'Added Rating',
                    'rating' => $rating
                     ], 201);
            } else
            {
                return response()->json([
                    'message' => 'You can only rate after finishing a reservation'
                ], 403);
            }
        }catch(ModelNotFoundException $e){
            return response()->json([
                'error'=>'the apartment is not found'
            ],404);
        }
    }
    //____________________________________________________
    public function showRatingsForApartment($apartmentId)//عرض التقييمات لشقة معينة مع المتوسط
    {
        try
        {
            $user_id=Auth::user()->id;
            $apartment=Apartment::with('ratings.user')->findOrFail($apartmentId);
            $reservations=$apartment->reservations;
            foreach($reservations as $reservation)
            {
                if($reservation->apartment->user_id !== $user_id)
                {
                    return response()->json(['message' => 'Unauthorized'], 403);
                }
            }
            // حساب المتوسط
            $averageRating=$apartment->ratings()->avg('rating_value');

            return response()->json([
                'apartment'=>$apartment->id,
                'average_rating'=>round($averageRating, 2),
                'ratings'=>$apartment->ratings->map(function ($rating) {
                    return [
                        'user'=>$rating->user->profile->first_name ?? 'nameless',
                        'stars'=>$rating->rating_value,
                        'comment'=>$rating->comment,
                    ];
                }),
            ]);
        }catch(ModelNotFoundException $e){
            return response()->json([
                'error'=>'the apartment is not found'
            ],404);
        }
    }
    //____________________________________________________
    public function getAllApartmentsICAR()//جلب كل الشقق مع التفاصيل الخارجية
    {
        $apartments=Apartment::all();
        $data=$apartments->map(function($apartment)
        {
            $user = User::findOrFail($apartment->user_id);
            return [
            'id'            => $apartment->id,
            'first_name_owner' => $user->profile->first_name,
            'last_name_owner' => $user->profile->last_name,
            'image' => $apartment->image ,
            'city' => $apartment->city,
            'area' => $apartment->area,
            'average_rating'=> round($apartment->ratings()->avg('rating_value'), 2),
            'price'=> $apartment->price,
            'room'=> $apartment->room,
            'bath_room'=>$apartment->bath_room,
            'is_available'=>$apartment->is_available,
            'is_favorate' => Auth::user()->favoritesApartment->contains($apartment->id) ? 1 : 0,
        ];
        });
        return response()->json([
            'all apartments:' => $data
        ], 200);
    }
    //____________________________________________________
    public function getApartmentWithAllDetailed($apartmentId)//عرض شقة معينة مع كل التفاصيل
    {
        try
        {
            $apartment = Apartment::with(['ratings.user'])->findOrFail($apartmentId);
            $data =
            [
                'id'            => $apartment->id,
                'city'          => $apartment->city,
                'area'          => $apartment->area,
                'average_rating'=> round($apartment->ratings()->avg('rating_value'), 2),
                'space'         => $apartment->space,
                'address'          => $apartment->address,
                'room'=> $apartment->room,
                'bath_room'=>$apartment->bath_room,
                'price'         => $apartment->price,
                'is_available'  => $apartment->is_available,
                'is_favorate' => Auth::user()->favoritesApartment->contains($apartment->id) ? 1 : 0,
                'ratings'       => $apartment->ratings->map(function ($rating) {
                    return [
                        'user'    => $rating->user->profile->first_name ?? 'nameless',
                        'stars'   => $rating->rating_value,
                        'comment' => $rating->comment,
                    ];
                }),
            ];

            return response()->json([
            'apartment:'=> $data
            ],200);
        }catch(ModelNotFoundException $e){
            return response()->json([
                'error'=>'the apartment is not found'
            ],404);
        }
    }
    //____________________________________________________
    public function getFilterApartments(Request $request)//فلترة الشقق
    {
        $query = Apartment::query();

        // تحقق من المدخلات
        if ($request->filled('city'))
        {
            $query->whereRaw('LOWER(city) = ?', [strtolower($request->input('city'))]);
        }

        if ($request->filled('area'))
        {
            $query->whereRaw('LOWER(area) = ?', [strtolower($request->input('area'))]);
        }

        if ($request->filled('space_min') && $request->filled('space_max'))
        {
            $query->whereBetween('space', [$request->input('space_min'), $request->input('space_max')]);
        }

        if ($request->filled('price_min') && $request->filled('price_max'))
        {
            $query->whereBetween('price', [$request->input('price_min'), $request->input('price_max')]);
        }

        if ($request->filled('room'))
        {
            $query->where('room', $request->input('room'));
        }

        if ($request->filled('bath_room'))
        {
            $query->where('bath_room', $request->input('bath_room'));
        }

        $apartments = $query->get();

        if ($apartments->isEmpty())
        {
            return response()->json([
                'message' => 'No apartments found with the specified filters.'
            ], 404);
        }

        //تحديد طريقة العرض
        $result = $apartments->map(function ($apartment)
        {
            $user = $apartment->user;
            return [
                'id'               => $apartment->id,
                'first_name_owner' => $user->profile->first_name ?? null,
                'last_name_owner'  => $user->profile->last_name ?? null,
                'image'            => $apartment->image,
                'city'             => $apartment->city,
                'area'             => $apartment->area,
                'average_rating'   => round($apartment->ratings()->avg('rating_value'), 2),
                'price'            => $apartment->price,
                'room'             => $apartment->room,
                'bath_room'        => $apartment->bath_room,
                'is_available'     => $apartment->is_available,
                'is_favorate' => Auth::user()->favoritesApartment->contains($apartment->id) ? 1 : 0,
            ];
        });
        return response()->json([
            'apartments'=>$result
            ],200);
    }

}
