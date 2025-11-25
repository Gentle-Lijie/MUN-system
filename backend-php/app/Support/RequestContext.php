<?php

namespace App\Support;

use App\Models\User;

class RequestContext
{
    private static ?User $user = null;

    private static bool $loggingMuted = false;

    public static function setUser(?User $user): void
    {
        self::$user = $user;
    }

    public static function user(): ?User
    {
        return self::$user;
    }

    public static function userId(): ?int
    {
        return self::$user?->id;
    }

    public static function reset(): void
    {
        self::$user = null;
        self::$loggingMuted = false;
    }

    public static function isLoggingMuted(): bool
    {
        return self::$loggingMuted;
    }

    public static function muteLogging(bool $mute = true): void
    {
        self::$loggingMuted = $mute;
    }

    /**
     * @template T
     * @param callable():T $callback
     * @return T
     */
    public static function withoutQueryLogging(callable $callback)
    {
        $previous = self::$loggingMuted;
        self::$loggingMuted = true;
        try {
            /** @var T */
            $result = $callback();
            return $result;
        } finally {
            self::$loggingMuted = $previous;
        }
    }
}
