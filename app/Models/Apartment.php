<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Apartment extends Model
{
    use HasFactory;
    protected $fillable =
    [
        'user_id',
        'city',
        'area',
        'space',
        'address',
        'image',
        'room',
        'bath_room',
        'price',
        'is_available',
    ];
    protected $table = 'apartments';
    //____________________________________________________________
    public function reservations()//كل شقة ممكن تكون موجودة باكثر من حجز
    {
        return $this->hasMany(Reservation::class);
    }
    //____________________________________________________________
     public function user()// كل شقة ينتمي ليوزر
    {
        return $this->belongsTo(User::class);
    }
    //____________________________________________________________
    public function favoritesByUser()
    {
        return $this->belongsToMany(User::class,'favorites','apartment_id','user_id');
    }
    //____________________________________________________________
    public function favorites()//كل شقة ممكن تكون موجودة باكثر من مفضلة
    {
        return $this->hasMany(Favorit::class);
    }
    //____________________________________________________________
    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }
    //____________________________________________________________
    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }
    //____________________________________________________________

}
