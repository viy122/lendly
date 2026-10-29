<?php

namespace App\Console\Commands;

use App\Models\IdentitySubmission;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneIdentityDocuments extends Command
{
    protected $signature = 'identity:prune-documents';

    protected $description = 'Cancel expired ID reviews and remove expired or decided review photos';

    public function handle(): int
    {
        IdentitySubmission::where('status', 'pending')->where('created_at', '<=', now()->subDays(30))
            ->select(['id', 'user_id'])->chunkById(100, function ($submissions) {
                foreach ($submissions as $snapshot) {
                    DB::transaction(function () use ($snapshot) {
                        User::withTrashed()->whereKey($snapshot->user_id)->lockForUpdate()->first();
                        $submission = IdentitySubmission::whereKey($snapshot->id)->lockForUpdate()->first();
                        if ($submission->status === 'pending' && $submission->created_at->lte(now()->subDays(30))) {
                            $submission->update(['status' => 'cancelled']);
                        }
                    });
                }
            });
        IdentitySubmission::where('status', '!=', 'pending')->whereNotNull('front_path')
            ->chunkById(100, fn ($submissions) => $submissions->each->purgeDocuments());

        $this->info('Expired reviews cancelled and decided review photos removed.');

        return self::SUCCESS;
    }
}
