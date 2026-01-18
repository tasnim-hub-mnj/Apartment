<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;


class Reservation extends Model
{
    use HasFactory;
    protected $fillable =
    [
        'user_id',
        'apartment_id',
        'approv_status_reserv',
        'status',
        'start_date',
        'end_date',
        'status_pay',
        'required_amount',
    ];
    protected $table = 'reservations';
    //____________________________________________________________
    public function apartment()//لكل حجز شقة واحدة
    {
        return $this->belongsTo(Apartment::class);
    }
    //____________________________________________________________
    public function user()//لكل حجز مستخدم واحد
    {
        return $this->belongsTo(User::class);
    }
    //____________________________________________________________
    // // تشفير رقم البطاقة
    // public function setCardNumberAttribute($value)
    // {
    //     $this->attributes['card_number'] = $value ? Crypt::encryptString($value) : null;
    // }
    // //____________________________________________________________
    // // فك تشفير رقم البطاقة
    // public function getCardNumberAttribute($value)
    // {
    //     return $value ? Crypt::decryptString($value) : null;
    // }
    //____________________________________________________________
    public static function hasConflictWithApproved(
        $apartmentId,
        $start,
        $end,
        $excludeId=null,
        )
    {
        $start = Carbon::parse($start)->toDateString();
        $end = Carbon::parse($end)->toDateString();

        return self::where('apartment_id', $apartmentId)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))// استبعاد الحجز الحالي
            ->where('approv_status_reserv', 'approved')//*
            ->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start)
            ->exists();
    }
    //____________________________
    public static function hasConflict(
        $apartmentId,
        $start,
        $end,
        $excludeId=null,
        )
    {
        $start = Carbon::parse($start)->toDateString();
        $end = Carbon::parse($end)->toDateString();

        return self::where('apartment_id', $apartmentId)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))// استبعاد الحجز الحالي
            ->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start)
            ->exists();
    }

}

