<?php

namespace Tests\Unit;

use App\Support\AuthEmail;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AuthEmailTest extends TestCase
{
    #[DataProvider('emailAddresses')]
    public function test_email_normalization_preserves_other_domains_and_invalid_addresses(string $input, string $expected): void
    {
        $this->assertSame($expected, AuthEmail::normalize($input));
    }

    public static function emailAddresses(): array
    {
        return [
            [' Test.User+rent@GMAIL.COM ', 'testuser@gmail.com'],
            [' Test.User+rent@Example.COM ', 'test.user+rent@example.com'],
            ['test.user+legacy@googlemail.com', 'testuser@gmail.com'],
            ['test.user@gmail.com.example.com', 'test.user@gmail.com.example.com'],
            ['testuser@example.com@gmail.com', 'testuser@example.com@gmail.com'],
            ['testuser', 'testuser'],
        ];
    }
}
