<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class IdentityVerificationAttempt extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'status', 'input_hash', 'liveness_session_hash', 'expires_at',
    ];

    protected $hidden = ['input_hash', 'liveness_session_hash'];

    protected function casts(): array
    {
        return ['expires_at' => 'immutable_datetime'];
    }
}
