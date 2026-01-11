<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Profile;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {//**********Admin User***********/
        $users1=[
            'phone'=>'0123456789',
            'password'=>'123456',
            'role'=>'admin',
            'approval_status'=>'approved'
        ];
        $user=User::create($users1);

        $profil=[
            'user_id'=>$user->id,
            'first_name'=>'admin',
            'last_name'=>'admin'
        ];
        Profile::create($profil);

    //**********Owner User***********/
     $users2=[
            'phone'=>'0939002188',
            'password'=>'123456',
            'role'=>'owner',
            'approval_status'=>'approved'
        ];
        $user2=User::create($users2);

        $profil2=[
            'user_id'=>$user2->id,
            'first_name'=>'Omar',
            'last_name'=>'Kadireya',
            'birth_date'=>'1990-01-01'
        ];
        Profile::create($profil2);

    //**********1)Renter User***********/
        $users3=[
            'phone'=>'0939002187',
            'password'=>'123456',
            'role'=>'renter',
            'approval_status'=>'approved'
        ];
        $user3=User::create($users3);

        $profil3=[
            'user_id'=>$user3->id,
            'first_name'=>'Tasnim',
            'last_name'=>'Almonajed',
            'birth_date'=>'2005-09-20'
        ];
        Profile::create($profil3);
    //**********2)Renter User***********/
        $users4=[
            'phone'=>'0939002186',
            'password'=>'123456',
            'role'=>'renter',
            'approval_status'=>'approved'
        ];
        $user4=User::create($users4);

        $profil4=[
            'user_id'=>$user4->id,
            'first_name'=>'Mohammed',
            'last_name'=>'Saleh',
            'birth_date'=>'2004-05-14'
        ];
        Profile::create($profil4);
    }
}

