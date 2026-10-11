<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfflinePaymentSetting extends Model
{
    protected $fillable = ['id', 'method', 'instructions', 'enabled', 'updated_by'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }

    public static function current(): ?self
    {
        return self::find(1);
    }

    public static function methodLabel(string $method): string
    {
        return $method === 'cash' ? 'Cash collection' : 'Bank transfer';
    }
}
