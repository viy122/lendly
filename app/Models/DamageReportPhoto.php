<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DamageReportPhoto extends Model
{
    protected $fillable = [
        'damage_report_id',
        'path',
        'sort_order',
    ];

    public function damageReport(): BelongsTo
    {
        return $this->belongsTo(DamageReport::class);
    }

    public function url(): string
    {
        return asset('storage/'.$this->path);
    }
}
