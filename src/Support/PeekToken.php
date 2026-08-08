<?php

namespace Blemli\Peek\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class PeekToken
{
    /**
     * @var array<string, string>
     */
    protected static array $cache = [];

    public static function make(Model $record): string
    {
        $cacheKey = $record::class . ':' . $record->getKey();

        return static::$cache[$cacheKey] ??= Crypt::encryptString(json_encode([
            'm' => $record::class,
            'k' => $record->getKey(),
        ]));
    }

    /**
     * @return array{0: class-string<Model>, 1: int|string}|null
     */
    public static function parse(string $token): ?array
    {
        try {
            $payload = json_decode(Crypt::decryptString($token), true);
        } catch (DecryptException) {
            return null;
        }

        $model = $payload['m'] ?? null;
        $key = $payload['k'] ?? null;

        if (! is_string($model) || ! class_exists($model) || ! is_subclass_of($model, Model::class)) {
            return null;
        }

        if (! is_int($key) && ! is_string($key)) {
            return null;
        }

        return [$model, $key];
    }

    public static function flushCache(): void
    {
        static::$cache = [];
    }
}
