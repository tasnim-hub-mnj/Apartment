<?php

namespace Database\Seeders;

use App\Models\Reservation;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReservationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       $reservation1=[
            'user_id'=>3,
            'apartment_id'=>1,
            'status'=>'confirmed',
            'approv_status_reserv'=>'approved',
            'start_date'=>'2026-01-01',
            'end_date'=>'2026-01-10',
            'pay_method'=>'card',
            'card_number'=>'1234567890123456',
            'status_pay'=>'paid',
            'required_amount'=>'10000.00'
        ];
        Reservation::create($reservation1);

        $reservation2=[
            'user_id'=>3,
            'apartment_id'=>2,
            'status'=>'confirmed',
            'approv_status_reserv'=>'approved',
            'start_date'=>'2026-01-01',
            'end_date'=>'2026-01-5',
            'pay_method'=>'cash',
            'card_number'=>'null',
            'status_pay'=>'paid',
            'required_amount'=>'375.00'
        ];
        Reservation::create($reservation2);

        $reservation3=[
            'user_id'=>3,
            'apartment_id'=>1,
            'status'=>'confirmed',
            'approv_status_reserv'=>'approved',
            'start_date'=>'2025-12-01',
            'end_date'=>'2025-12-05',
            'pay_method'=>'cash',
            'card_number'=>'null',
            'status_pay'=>'paid',
            'required_amount'=>'375.00'
        ];
        Reservation::create($reservation3);
    }
}
