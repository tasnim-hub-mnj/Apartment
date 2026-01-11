<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory;
    protected $fillable =
    [
        'user_id',
        'apartment_id',
        'rating_value',
        'comment',
    ];

    protected $table = 'ratings';
    //____________________________________________________________
    public function apartment()
    {
        return $this->belongsTo(Apartment::class);
    }
    //____________________________________________________________
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
