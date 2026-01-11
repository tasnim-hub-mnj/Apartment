<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Apartment;
use Illuminate\Container\Attributes\Storage;

class ApartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $apartment1=[
            'user_id'=>2,
            'city'=>'Damascuse',
            'area'=>'midan',
            'space'=>'200',
            'size'=>'medium',
            'description'=>'3room,2bath',
            'price'=>'100',
        ];
        Apartment::create($apartment1);

        $apartment2=[
            'user_id'=>2,
            'city'=>'Damascuse',
            'area'=>'baramka',
            'space'=>'150',
            'size'=>'small',
            'description'=>'2room,1bath',
            'price'=>'75',
        ];
        Apartment::create($apartment2);


    }
}
