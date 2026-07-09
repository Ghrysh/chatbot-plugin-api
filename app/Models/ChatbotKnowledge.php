<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotKnowledge extends Model
{
    use HasFactory;

    protected $table = 'chatbot_knowledge';

    protected $fillable = [
        'client_id',
        'topic',
        'keywords',
        'response'
    ];
    
    protected $casts = [
        'keywords' => 'array',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
