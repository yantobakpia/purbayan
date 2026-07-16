<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    protected $fillable = ['name', 'email_or_phone', 'complaint_text', 'status', 'resolved_at'];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];
}
