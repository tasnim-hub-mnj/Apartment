<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable =
    [
        'user_id',
        'title',
        'message'
    ];
    protected $table = 'notifications';
    //___________________________________________________________
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
