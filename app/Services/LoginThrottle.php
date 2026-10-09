<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Login cooldown per email. Counter is only incremented on failure, cleared on success.
 *
 * ponytail: keyed per email (not IP) so the admin can reset one user and users behind the same NAT do not lock each other.
 * Ceiling: an attacker can deliberately lock someone else's account (3 wrong attempts) — mitigated by admin reset.
 * Upgrade: email+IP key + global per-IP limit.
 */
class LoginThrottle
{
    public const MAX_ATTEMPTS = 3;
    public const DECAY_SECONDS = 900;

    private static function hash(string $email): string
    {
        return sha1(mb_strtolower(trim($email)));
    }

    public static function attemptsKey(string $email): string
    {
        return 'login-cooldown:attempts:' . self::hash($email);
    }

    public static function lockKey(string $email): string
    {
        return 'login-cooldown:until:' . self::hash($email);
    }

    /** Serialize attempts per email so concurrent requests cannot slip past the limit / be double counted. */
    public static function guard(string $email, Closure $callback): mixed
    {
        return Cache::lock('login-cooldown:mutex:' . self::hash($email), 10)->block(5, $callback);
    }

    public static function attempts(string $email): int
    {
        return (int) Cache::get(self::attemptsKey($email), 0);
    }

    public static function availableIn(string $email): int
    {
        return max(0, (int) Cache::get(self::lockKey($email), 0) - now()->getTimestamp());
    }

    public static function isLocked(string $email): bool
    {
        return self::availableIn($email) > 0;
    }

    /** Record ONE failed attempt. Locks for DECAY_SECONDS counted from the attempt that hits MAX_ATTEMPTS. */
    public static function hit(string $email): int
    {
        $key = self::attemptsKey($email);
        Cache::add($key, 0, self::DECAY_SECONDS);
        $attempts = (int) Cache::increment($key);

        if ($attempts >= self::MAX_ATTEMPTS) {
            Cache::put(self::lockKey($email), now()->getTimestamp() + self::DECAY_SECONDS, self::DECAY_SECONDS);
            Cache::put($key, $attempts, self::DECAY_SECONDS); // counter expires together with the lock
        }

        return $attempts;
    }

    /** Clear only this user's keys — never flush the global cache. */
    public static function clear(string $email): void
    {
        Cache::forget(self::attemptsKey($email));
        Cache::forget(self::lockKey($email));
    }
}
