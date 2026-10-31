<?php

namespace App\Http\Controllers;

use App\Models\IdentitySubmission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class IdentityDocumentController extends Controller
{
    public function __invoke(Request $request, IdentitySubmission $submission, string $kind): BinaryFileResponse
    {
        $user = User::find($request->user()?->id);
        abort_unless($user && ! $user->isSuspended() && $user->hasVerifiedEmail()
            && ($user->isAdmin() || $user->id === $submission->user_id), 403);
        $submission->refresh();
        abort_unless($submission->status === 'pending' && $submission->created_at->gt(now()->subDays(30)), 404);
        abort_unless(in_array($kind, ['front', 'back', 'selfie'], true), 404);
        $path = $submission->{$kind.'_path'};
        abort_unless($path && str_starts_with($path, 'identity-documents/'.$submission->user_id.'/')
            && ! str_contains($path, '..') && Storage::disk('local')->exists($path), 404);

        $response = response()->file(Storage::disk('local')->path($path), [
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox", 'Referrer-Policy' => 'no-referrer',
        ]);
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
