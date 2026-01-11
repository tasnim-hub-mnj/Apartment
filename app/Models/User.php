<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable =
    [
        'phone',
        'password',
        'role',
        'approval_status',

    ];
    

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // 'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    //____________________________________________________________
    public function profile()//كل يوزر لديه بروفايل واحد
    {
        return $this->hasOne(Profile::class);
    }
    //____________________________________________________________
    public function apartments()//ممكن للمستخدم الواحد عندو اكتر من شقة
    {
        return $this->hasMany(Apartment::class);
    }
    //____________________________________________________________
    public function reservations()//عند كل مستدم اكثر من حجز
    {
        return $this->hasMany(Reservation::class);
    }
    //____________________________________________________________
    public function favoritesApartment()
    {
        return $this->belongsToMany(Apartment::class,'favorites','user_id','apartment_id');
    }
    //____________________________________________________________
    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }
    //____________________________________________________________
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
    //____________________________________________________________
    public function ownerConversations()
    {
        return $this->hasMany(Conversation::class,'owner_id');
    }
    //____________________________________________________________
    public function renterConversations()
    {
        return $this->hasMany(Conversation::class,'renter_id');
    }
    //____________________________________________________________
    public function messages()
    {
        return $this->hasMany(Message::class,'sender_id');
    }

}
