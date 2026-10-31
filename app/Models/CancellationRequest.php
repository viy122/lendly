<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CancellationRequest extends Model
{
    protected $fillable = ['rental_id', 'requested_by', 'reason', 'fee', 'status', 'reviewed_by', 'review_notes', 'reviewed_at'];

    protected function casts(): array
    {
        return ['fee' => 'decimal:2', 'reviewed_at' => 'datetime'];
    }
}
