<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerifiedSender extends Model
{
    use HasFactory;

    protected $table = 'verified_senders';  // Explicit table name

    protected $fillable = [
        'sender_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}