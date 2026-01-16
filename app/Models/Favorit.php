<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favorit extends Model
{
    use HasFactory;
    protected $fillable =
    [
        'user_id',
        'apartment_id',
    ];
    protected $table = 'favorites';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    //____________________________________________________________
    public function apartment()
    {
        return $this->belongsTo(Apartment::class);
    }
}
