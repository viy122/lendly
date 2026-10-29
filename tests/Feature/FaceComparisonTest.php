<?php

namespace Tests\Feature;

use App\Services\FaceComparison;
use Illuminate\Support\Facades\Process;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FaceComparisonTest extends TestCase
{
    public function test_it_returns_the_raw_local_similarity_and_passes_paths_as_arguments(): void
    {
        Process::fake(['*' => Process::result(output: '{"similarity":0.725}')]);

        $this->assertSame(0.725, app(FaceComparison::class)->compare('private/id image.jpg', 'private/selfie.jpg'));

        Process::assertRan(fn ($process) => is_array($process->command)
            && in_array('private/id image.jpg', $process->command, true)
            && in_array('private/selfie.jpg', $process->command, true));
    }

    #[DataProvider('invalidResults')]
    public function test_invalid_results_never_become_a_face_score(string $output, int $exitCode): void
    {
        Process::fake(['*' => Process::result(output: $output, errorOutput: 'PRIVATE PATH AND DATA', exitCode: $exitCode)]);

        try {
            app(FaceComparison::class)->compare('id.jpg', 'selfie.jpg');
            $this->fail('Invalid result was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('identity', $exception->errors());
            $this->assertStringNotContainsString('PRIVATE', $exception->getMessage());
        }
    }

    public static function invalidResults(): array
    {
        return [['{}', 0], ['{"similarity":"0.8"}', 0], ['{"similarity":1.1}', 0],
            ['{"similarity":-1.1}', 0], ['{"similarity":NaN}', 0], ['not json', 0], ['{"similarity":0.9}', 1]];
    }

    public function test_a_missing_face_has_a_useful_photo_validation_error(): void
    {
        Process::fake(['*' => Process::result(output: '{"error":"selfie_face_count"}', exitCode: 2)]);

        try {
            app(FaceComparison::class)->compare('id.jpg', 'selfie.jpg');
            $this->fail('An invalid selfie was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('selfie', $exception->errors());
        }
    }
}
