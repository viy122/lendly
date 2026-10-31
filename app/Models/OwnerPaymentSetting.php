<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OwnerPaymentSetting extends Model
{
    protected $fillable = ['user_id', 'provider', 'account_name', 'instructions', 'qr_path', 'enabled'];

    protected $hidden = ['qr_path'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }

    public static function currentFor(int $userId): ?self
    {
        return self::where('user_id', $userId)->first();
    }

    public static function deleteUnreferencedQr(string $path): void
    {
        DB::afterCommit(function () use ($path) {
            if (! self::where('qr_path', $path)->exists()
                && ! Rental::where('payment_destination->qr_path', $path)->exists()
                && ! PaymentSubmission::where('payment_destination->qr_path', $path)->exists()) {
                Storage::disk('local')->delete($path);
            }
        });
    }
}
