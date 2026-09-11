<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupLog extends Model
{
    protected $fillable = [
        'type',
        'status',
        'filename',
        'error',
        'started_at',
        'completed_at',
        'verification_status',
        'verified_at',
        'verification_error',
        'restore_verification_status',
        'restore_verified_at',
        'restore_verification_error',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'verified_at' => 'datetime',
        'restore_verified_at' => 'datetime',
    ];
}
