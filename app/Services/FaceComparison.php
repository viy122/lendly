<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Validation\ValidationException;
use Throwable;

class FaceComparison
{
    public function compare(string $frontPath, string $selfiePath): float
    {
        $unavailable = 'Face comparison is temporarily unavailable. Please try again later or contact support.';

        try {
            $result = Process::timeout(45)->env(['OPENCV_LOG_LEVEL' => 'SILENT'])->run([
                config('services.face_match.python'), base_path('scripts/face-match/compare.py'),
                '--models', config('services.face_match.models'), '--front', $frontPath, '--selfie', $selfiePath,
            ]);
            $output = json_decode($result->output(), true);
        } catch (Throwable) {
            throw ValidationException::withMessages(['identity' => $unavailable]);
        }

        if ($result->exitCode() === 2) {
            $error = is_array($output) ? ($output['error'] ?? '') : '';
            $messages = [
                'front_face_count' => ['front' => 'Use a clear ID front with a large, unobstructed portrait.'],
                'selfie_face_count' => ['selfie' => 'Use a clear selfie with exactly one face, looking at the camera.'],
                'front_invalid' => ['front' => 'The ID image could not be read. Please upload a new image.'],
                'selfie_invalid' => ['selfie' => 'The selfie could not be read. Please upload a new image.'],
            ];
            if (isset($messages[$error])) {
                throw ValidationException::withMessages($messages[$error]);
            }
        }

        $score = is_array($output) ? ($output['similarity'] ?? null) : null;
        if (! $result->successful() || (! is_float($score) && ! is_int($score))
            || ! is_finite((float) $score) || $score < -1 || $score > 1) {
            throw ValidationException::withMessages(['identity' => $unavailable]);
        }

        return (float) $score;
    }
}
