<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FavoriteController extends Controller
{
    public function addToFavorites(int $apartmentId)//اضافة الى المفضلة
    {
        try
        {
            Apartment::findOrFail($apartmentId);
            Auth::user()->favoritesApartment()->syncWithoutDetaching($apartmentId);

            try//إرسال إشعار للمستأجر
            {
                $renter_id=Auth::id();
                $renter=User::findOrFail($renter_id);
                $token_fcm=$renter->profile->token_fcm;
                if (!$token_fcm)
                {
                    Log::warning("User $renter_id  Added a apartment for Favorites but has no FCM token.");
                    return response()->json(['message' => 'user Added a apartment for Favorites, but no token found.']);
                }

                    $messaging = app('firebase.messaging');

                    $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token',$token_fcm)
                        ->withNotification(\Kreait\Firebase\Messaging\Notification::create("Added a apartment for Favorites", "Your apartment was added to favorites."));

                    $response = $messaging->send($message);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }

            return response()->json([
                'message'=>'Added To Favorite List'
            ],201);
        }catch(ModelNotFoundException $e){
            return response()->json([
                'error'=>'the apartment is not found'
            ],404);
        }
    }
    //____________________________________________________
    public function removeFromFavorites(int $apartmentId)//حذف من المفضلة
    {
        try
        {
            $apartment=Apartment::findOrFail($apartmentId);
            Auth::user()->favoritesApartment()->detach($apartmentId);

            try//إرسال إشعار للمستأجر
            {
                $renter_id=Auth::id();
                $renter=User::findOrFail($renter_id);
                $token_fcm=$renter->profile->token_fcm;
                if (!$token_fcm)
                {
                    Log::warning("User $renter_id  Deleted a apartment for Favorites but has no FCM token.");
                    return response()->json(['message' => 'user Deleted a apartment for Favorites, but no token found.']);
                }

                    $messaging = app('firebase.messaging');

                    $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token',$token_fcm)
                        ->withNotification(\Kreait\Firebase\Messaging\Notification::create("Deleted a apartment for Favorites", "Your apartment was deleted from favorites."));

                    $response = $messaging->send($message);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }

            return response()->json([
                'message'=>'Removed From Favorite List'
            ],200);
        }catch(ModelNotFoundException $e){
            return response()->json([
                'error'=>'the apartment is not found'
            ],404);
        }
    }
    //____________________________________________________
    public function countFavorites()//عدد المفضلة
    {
        $count_favorites=Auth::user()->favoritesApartment->count();
        return response()->json([
            'message'=>'Number of Favorite Apartments :',
            'count'=>$count_favorites
        ],200);
    }
    //____________________________________________________
    public function getAllFavoritesICAR()//جلب كل المفضلة مع التفاصيل الخارجية
    {
        $favorites=Auth::user()->favoritesApartment()->get();
        $favorites_rating=$favorites->map(function($apartment)
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
            'Favorite Apartments'=> $favorites_rating
        ],200);
    }
    //____________________________________________________
    public function getApartmentWithAllDetailed($apartmentId)//عرض شقة معينة مع التفاصيل الخارجية
    {
        try
        {
            $apartment = Apartment::with(['ratings.user'])->findOrFail($apartmentId);
            $data =
            [
                'id'               => $apartment->id,
                'image'            => $apartment->image,
                'city'             => $apartment->city,
                'area'             => $apartment->area,
                'space'            => $apartment->space,
                'address'          => $apartment->address,
                'average_rating'   => round($apartment->ratings()->avg('rating_value'), 2),
                'price'            => $apartment->price,
                'room'             => $apartment->room,
                'bath_room'        => $apartment->bath_room,
                'is_available'     => $apartment->is_available,
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

}
