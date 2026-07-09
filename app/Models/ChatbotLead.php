<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotLead extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'session_id',
        'name',
        'phone',
        'ip_address',
        'topic_context',
        'contact_info',
        'chat_history',
        'last_message',
        'live_chat_status',
        'admin_id',
        'status'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
