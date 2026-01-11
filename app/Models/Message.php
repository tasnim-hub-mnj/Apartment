<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable =
    [
        'conversation_id',
         'sender_id',
          'content'
    ];
    // protected $table = 'messages';
//___________________________________________________________
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
//___________________________________________________________
    public function sender()//المُرسل
    {
        return $this->belongsTo(User::class,'sender_id');
    }

}
