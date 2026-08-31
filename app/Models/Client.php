<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $guarded = [];

    protected $casts = [
        'db_allowed_tables' => 'array',
        'db_allow_read' => 'boolean',
    ];
}
