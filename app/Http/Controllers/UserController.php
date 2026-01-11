<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Profile;
use App\Models\Token_fcm;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function register(RegisterRequest $request)//تسجيل مستخدم
    {

        $user=User::create(//user create
            [
                'phone'=>$request->phone,
                'password'=>Hash::make($request->password),
                'role'=>$request->role,
            ]);
        $data=
            [
                'user_id'=> $user->id ,
                'first_name'=> $request->first_name,
                'last_name'=> $request->last_name,
                'birth_date'=> $request->birth_date,
                'token_fcm'=> $request->token_fcm,
            ];

        if($request->hasFile('personal_photo'))
        {
            $path1=$request->file('personal_photo')->store('profile','public');
            $data['personal_photo']=$path1;
        }
        if($request->hasFile('identity_photo'))
        {
            $path2=$request->file('identity_photo')->store('identity','public');
            $data['identity_photo']=$path2;
        }
        $profile=Profile::create($data);//profile create

        $token_fcm = $profile->token_fcm;
        $test=Token_fcm::where('token_fcm',$token_fcm)->first();
        if($test)
        {
            $user->delete();
           return response()->json([
            'message'=>'You can not register in this applecation, you are deleted',
            ],404);
        }

        return response()->json([
            'message'=>'User Register Successfully ',
            'User'=>$user,
            'profile'=>$profile
        ],201);
    }
//__________________________________________________________________________
    public function login(Request $request)//تسجيل دخول
    {
        $request->validate([
            'phone' =>'required|string|min:10|max:10',
            'password'=>'required|string'
        ]);
        if(!Auth::attempt($request->only('phone','password')))
            return response()->json([
            'message'=>'invalid password or phone'
        ], 401);
        $user=User::where('phone',$request->phone)->firstOrFail();
        $token=$user->createToken('auth_token')->plainTextToken;
        $user->profile;
        return response()->json([
            'message'=>'User Logged In Successfully',
            'Token'=>$token,
            'User'=>$user
        ], 201);
    }
//__________________________________________________________________________
    public function logout(Request $request)//تسجيل خروج
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message'=>'User Successfully Log Out'
        ],200);
    }
//__________________________________________________________________________

}
