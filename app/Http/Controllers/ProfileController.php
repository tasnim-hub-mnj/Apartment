<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProfileRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    public function UpdateProfile(UpdateProfileRequest $request)//تعديل
    {
            $user_id=Auth::user()->id;
            $profile_id=Auth::user()->profile->id;
            $profile=Profile::findOrFail($profile_id);
            $data=$request->validated();

            if($request->hasFile('personal_photo'))
            {
                if ($profile->personal_photo)
                {
                    Storage::disk('public')->delete($profile->personal_photo);
                }
                $path1 = $request->file('personal_photo')->store('profile', 'public');
                $profile->personal_photo = $path1;
                $data['personal_photo']=$path1;
            }
            $profile->update($data);

            // try//إرسال إشعار للمستخدم
            // {
            //     $user_id=Auth::user()->id;
            //     $user=User::findOrFail($user_id);
            //     $token_fcm=$user->profile->token_fcm;
            //     if (!$token_fcm)
            //     {
            //         Log::warning("User $user_id  updated his profile but has no FCM token.");
            //         return response()->json(['message' => 'user his profile updated, but no token found.']);
            //     }

            //         $messaging = app('firebase.messaging');

            //         $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token',$token_fcm)
            //             ->withNotification(\Kreait\Firebase\Messaging\Notification::create("Updated your profile", "Your profile was updated."));

            //         $response = $messaging->send($message);
            // } catch (\Exception $e) {
            //     return response()->json(['error' => $e->getMessage()], 500);
            // }

            return response()->json([
                'message'=>'Profile updated successfully',
                'profile'=>$profile,
            ],200);
    }
    //_____________________________________________________________
    public function getUserProfile()//طباعة بروفايل المستخدم الحالي
    {
        $user_id=Auth::user()->id;
        $user = User::findOrFail($user_id);
        $profile = $user->profile;

        return response()->json([
            'your profile: '=>
            [
                'personal_photo'=>$profile->personal_photo,
                'first_name'=>$profile->first_name,
                'last_name'=>$profile->last_name,
                'birth_date'=>$profile->birth_date,
                'identity_photo'=>$profile->identity_photo,
            ]
        ], 200);
    }

}



