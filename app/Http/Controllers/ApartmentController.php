<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterApartementRequest;
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
use PhpParser\Node\Expr\Cast\Double;
use Illuminate\Support\Facades\Storage;

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

            NotificationService::send(
                $reservation->user_id,
                'Reservation rejected',
                'Your reservation was rejected because the apartment was deleted'
            );
        }

        $apartment->delete();

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
    public function getApartmentsCity(string $city)//جلب الشقق حسب المدينة
    {
        $apartments=Apartment::where('city',$city)->get();
        if ($apartments->isEmpty())
        {
            return response()->json([
                'message' => 'No apartments found in the specified city.'
            ], 404);
        }

        return response()->json($apartments,200);
    }
    //____________________________________________________
    public function getApartmentsArea(string $area)//جلب الشقق حسب المنطقة
    {
        $apartments=Apartment::where('area',$area)->get();
         if ($apartments->isEmpty())
        {
            return response()->json([
                'message' => 'No apartments found in the specified area.'
            ], 404);
        }

        return response()->json($apartments,200);
    }
    //____________________________________________________
    public function getApartmentsSpace(float $space)//جلب الشقق حسب المساحة
    {
        $apartments=Apartment::where('space','<=',$space)->get();
         if ($apartments->isEmpty())
        {
            return response()->json([
                'message' => 'No apartments found with the specified space.'
            ], 404);
        }

        return response()->json($apartments,200);
    }
    //____________________________________________________
    public function getApartmentsSize(string $size)//جلب الشقق حسب الحجم
    {
        $apartments=Apartment::where('size',$size)->get();
         if ($apartments->isEmpty())
        {
            return response()->json([
                'message' => 'No apartments found in the specified area.'
            ], 404);
        }

        return response()->json($apartments,200);
    }
    //____________________________________________________
    public function getApartmentsPrice(float $price)//جلب الشقق حسب السعر
    {
        $apartments=Apartment::where('price','<=',$price)->get();
         if ($apartments->isEmpty()) {
            return response()->json([
                'message' => 'No apartments found within the specified price range.'
            ], 404);
        }

        return response()->json($apartments,200);
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
        $apartment=Apartment::with('ratings.user')->findOrFail($apartmentId);
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
    }
    //____________________________________________________
    public function getAllApartmentsICAR()//جلب كل الشقق مع المدينة والمنطقة والتقييم و الصورة
    {
        $apartments=Apartment::all();
        $data=$apartments->map(function($apartment)
        {
            $user = User::findOrFail($apartment->user_id);
            return [
            //'user_id' => $apartment->user_id,
            //'user' => $user,
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
        ];
        });
        return response()->json($data,200);
    }
    //____________________________________________________
    public function getApartmentWithAllDetailed($apartmentId)//عرض شقة معينة مع التقييمات والمتوسط
    {
        try
        {
            $apartment = Apartment::with(['ratings.user'])->findOrFail($apartmentId);
            $data = [
                // 'id'            => $apartment->id,
                'city'          => $apartment->city,
                'area'          => $apartment->area,
                'average_rating'=> round($apartment->ratings()->avg('rating_value'), 2),
                'space'         => $apartment->space,
                'size'          => $apartment->size,
                'room'=> $apartment->room,
                'bath_room'=>$apartment->bath_room,
                'price'         => $apartment->price,
                'is_available'  => $apartment->is_available,
                'ratings'       => $apartment->ratings->map(function ($rating) {
                    return [
                        'user'    => $rating->user->profile->first_name ?? 'nameless',
                        'stars'   => $rating->rating_value,
                        'comment' => $rating->comment,
                    ];
                }),
            ];

            return response()->json($data, 200);
        }catch(ModelNotFoundException $e){
            return response()->json([
                'error'=>'the apartment is not found'
            ],404);
        }
    }
    // //____________________________________________________
    // public function getAllApartmentsWithAllDetailed()//عرض كل الشقق مع التقييمات والمتوسط
    // {
    //     $apartments = Apartment::with(['ratings.user'])->get();

    //     $data = $apartments->map(function ($apartment)
    //     {
    //         return [
    //             // 'id'            => $apartment->id,
    //             'city'          => $apartment->city,
    //             'area'          => $apartment->area,
    //             'average_rating'=> round($apartment->ratings()->avg('rating_value'), 2),
    //             'space'         => $apartment->space,
    //             'size'          => $apartment->size,
    //             'room'=> $apartment->room,
     //           'bath_room'=>$apartment->bath_room,
    //             'price'         => $apartment->price,
    //             'is_available'  => $apartment->is_available,
    //             'ratings'       => $apartment->ratings->map(function ($rating) {
    //                 return [
    //                     'user'    => $rating->user->profile->first_name ?? 'nameless',
    //                     'stars'   => $rating->rating_value,
    //                     'comment' => $rating->comment,
    //                 ];
    //             }),
    //         ];
    //     });

    //     return response()->json($data, 200);
    // }
    //____________________________________________________
    // public function getApartmentCAR($apartmentId)//جلب المدينة والمنطقة والتقييم لشقة معينة
    // {
    //     $apartment=Apartment::findOrFail($apartmentId);
    //     return response()->json([
    //         'city'=>$apartment->city,
    //         'area'=>$apartment->area,
    //         'rating'=>$apartment->averageRating()
    //     ],200);
    // }
    //____________________________________________________
    // public function getAverageRating($apartmentId)//جلب متوسط التقييم لشقة معينة
    // {
    //     try
    //     {
    //         $apartment=Apartment::findOrFail($apartmentId);
    //         $averageRating=$apartment->averageRating();
    //         return response()->json([
    //             'apartment_id'=>$apartmentId,
    //             'average_rating'=>round($averageRating,2)
    //         ],200);
    //     }catch(ModelNotFoundException $e){
    //         return response()->json([
    //             'error'=>'the apartment is not found'
    //         ],404);
    //     }
    // }
    //____________________________________________________
    // public function getApartmentsWithRatings()//جلب كل الشقق مع التقييمات
    // {
    //     $apartments=Apartment::with('ratings')->get();
    //     return response()->json($apartments,200);
    // }
    //____________________________________________________
    // public function getRating()// جلب التقييمات مرتبة لمستخدم ما
    // {
    //     $rating=Rating::orderByRaw("FIELD(evaluation,'excellent','good','medium','bad')")->get();
    //     return response()->json($rating,200);
    // }
    //____________________________________________________
     // // تحقق من حالة الحساب
        // if (Auth::user()->approval_status !== 'approved')
        // {
        //     return response()->json([
        //         'message' => 'your account is awatiting approval of admin'
        //     ], 403);
        // }
 // public function isFavorite($apartmentId)//التحقق من المفضلة
    // {
    //     try
    //     {
    //         Apartment::findOrFail($apartmentId);
    //         $is_favorite=Auth::user()->favoritesApartment->where('id',$apartmentId)->isNotEmpty();
    //         return response()->json([
    //             'apartment_id'=>$apartmentId,
    //             'is_favorite'=>$is_favorite
    //         ],200);
    //     }catch(ModelNotFoundException $e){
    //         return response()->json([
    //             'error'=>'the apartment is not found'
    //         ],404);
    //     }
    // }
    //____________________________________________________
    // public function show(int $apartmentId)//طباعة صف (شقة)
    // {
    //    try
    //   {
    //    $user_id=Auth::user()->id;
    //    $apartment=Apartment::findOrFail($apartmentId);
    //    if($apartment->user_id != $user_id)
    //         return response()->json(['message'=>'Unauthorized'],403);

    //    return response()->json($apartment,200);
    //   }catch(ModelNotFoundException $e){
    //         return response()->json([
    //             'error'=>'the apartment is not found'
    //         ],404);
    //   }
    // }
//____________________________________________________
    // public function getApartmentUser($apartmentId)//طباعة صاحب الشقة
    // {
    //     try
    //     {
    //     $apartment = Apartment::findOrFail($apartmentId);
    //     $user = $apartment->user;
    //     return response()->json($user, 200);
    //     }catch(ModelNotFoundException $e){
    //         return response()->json([
    //             'error'=>'the apartment is not found'
    //         ],404);
    //     }
    // }
    //____________________________________________________
    // public function getApartmentsFilter(FilterApartementRequest $request)//فلترة الشقق
    // {
    //     $query = Apartment::query();

    //     if ($request->has('city'))
    //     {
    //         $query->where('city', $request->city);
    //     }
    //     if ($request->has('area'))
    //     {
    //         $query->where('area', $request->area);
    //     }
    //     if ($request->has('space'))
    //     {
    //         $query->where('space', '>=', $request->space);//اكبر من او تساوي المساحة المدخلة
    //     }
    //     if ($request->has('size'))
    //     {
    //         $query->where('size', $request->size);
    //     }
    //     if ($request->has('price'))
    //     {
    //         $query->where('price', '<=', $request->price);//اقل من او تساوي السعر المدخل
    //     }
    //     $apartments = $query->get();

    //     if ($apartments->isEmpty())
    //     {
    //         return response()->json([
    //             'message' => 'No apartments found.'
    //         ],404);
    //     }

    //     return response()->json($apartments->getAllApartmentsCAR(),200);//------------
    // }

}
