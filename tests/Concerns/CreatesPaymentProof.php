<?php

namespace Tests\Concerns;

use Illuminate\Http\UploadedFile;

trait CreatesPaymentProof
{
    private function paymentProof(): UploadedFile
    {
        // A real one-pixel PNG keeps MIME validation independent of PHP GD.
        return UploadedFile::fake()->createWithContent('receipt.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aDd8AAAAASUVORK5CYII='
        ));
    }
}
