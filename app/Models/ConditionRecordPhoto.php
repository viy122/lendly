<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConditionRecordPhoto extends Model
{
    protected $fillable = [
        'condition_record_id',
        'path',
        'sort_order',
    ];

    public function conditionRecord(): BelongsTo
    {
        return $this->belongsTo(ConditionRecord::class);
    }

    public function url(): string
    {
        return asset('storage/'.$this->path);
    }
}
