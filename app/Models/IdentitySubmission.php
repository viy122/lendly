<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class IdentitySubmission extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['front_path', 'back_path', 'selfie_path', 'profile_hash'];

    protected function casts(): array
    {
        return ['face_similarity' => 'float', 'consented_at' => 'datetime', 'reviewed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by')->withTrashed();
    }

    public static function profileHash(User $member): string
    {
        return hash_hmac('sha256', json_encode([$member->id, $member->name, $member->email]), config('app.key'));
    }

    public function purgeDocuments(): void
    {
        $paths = array_filter([$this->front_path, $this->back_path, $this->selfie_path]);
        DB::afterCommit(function () use ($paths) {
            if (Storage::disk('local')->delete($paths)) {
                static::whereKey($this->id)->update(['front_path' => null, 'back_path' => null, 'selfie_path' => null]);
            }
        });
    }

    public static function cancelPending(int $userId): void
    {
        static::where('user_id', $userId)->where('status', 'pending')->get()->each(function ($submission) {
            $submission->update(['status' => 'cancelled']);
            $submission->purgeDocuments();
        });
    }
}
