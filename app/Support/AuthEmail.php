<?php

namespace App\Support;

use App\Models\User;

class AuthEmail
{
    public static function normalize(string $email): string
    {
        $email = strtolower(trim($email));
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        if (! in_array($domain, ['gmail.com', 'googlemail.com'], true)) {
            return $email;
        }

        return str_replace('.', '', explode('+', $local, 2)[0]).'@gmail.com';
    }

    public static function findUser(string $email, ?int $ignoreId = null): ?User
    {
        $input = strtolower(trim($email));
        $email = self::normalize($input);
        $users = User::query()->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId));
        $exact = (clone $users)->whereRaw('LOWER(email) = ?', [$input])->first();

        if ($exact || ! str_ends_with($email, '@gmail.com')) {
            return $exact;
        }

        if ($input !== $email) {
            $exact = (clone $users)->whereRaw('LOWER(email) = ?', [$email])->first();

            if ($exact) {
                return $exact;
            }
        }

        // shortcut: legacy Gmail aliases require a scan; add an indexed canonical column at larger account volumes.
        foreach ($users->where(fn ($query) => $query
            ->whereRaw('LOWER(email) LIKE ?', ['%@gmail.com'])
            ->orWhereRaw('LOWER(email) LIKE ?', ['%@googlemail.com']))->cursor() as $user) {
            if (self::normalize($user->email) === $email) {
                return $user;
            }
        }

        return null;
    }

    public static function isTaken(string $email, ?int $ignoreId = null): bool
    {
        return self::findUser($email, $ignoreId) !== null;
    }
}
