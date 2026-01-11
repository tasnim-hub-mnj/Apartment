<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;
    protected $fillable =
    [
        'user_id',
        'first_name',
        'last_name',
        'personal_photo',
        'birth_date',
        'identity_photo',
        'token_fcm',
    ];
    protected $table = 'profiles';
    //____________________________________________________________
    public function user()// كل بروفايل ينتمي ليوزر
    {
        return $this->belongsTo(User::class);
    }
}
