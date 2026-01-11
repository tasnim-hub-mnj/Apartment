<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable =
    [
        'apartment_id',
         'owner_id',
          'renter_id'
    ];
    // protected $table = 'conversations';
    //___________________________________________________________
    public function messages()
    {
        return $this->hasMany(Message::class);
    }
    //___________________________________________________________
    public function owner()// المؤجر
    {
        return $this->belongsTo(User::class,'owner_id');
    }
    //___________________________________________________________
    public function renter()// المستأجر
    {
        return $this->belongsTo(User::class,'renter_id');
    }
    //___________________________________________________________
    public function apartment()
    {
        return $this->belongsTo(Apartment::class);
    }

}
