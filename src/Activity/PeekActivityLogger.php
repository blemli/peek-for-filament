<?php

namespace Blemli\Peek\Activity;

use Blemli\Peek\PeekPlugin;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Throwable;

class PeekActivityLogger
{
    /**
     * Overridable for tests simulating an absent spatie/laravel-activitylog.
     *
     * @var (callable(): bool)|null
     */
    public static $availabilityCheck = null;

    public function log(Model $record): void
    {
        try {
            if (! $this->isAvailable()) {
                return;
            }

            $user = Filament::auth()->user();

            if (! $user) {
                return;
            }

            if (! $this->passesThrottle($record, $user)) {
                return;
            }

            activity((string) config('peek.activity_log.log_name', 'peek'))
                ->performedOn($record)
                ->causedBy($user)
                ->event('peeked')
                ->log('peeked');
        } catch (Throwable) {
            // Logging must never break the peek itself.
        }
    }

    protected function isAvailable(): bool
    {
        $check = static::$availabilityCheck ?? fn (): bool => function_exists('activity');

        return $check();
    }

    protected function passesThrottle(Model $record, Authenticatable $user): bool
    {
        $minutes = PeekPlugin::tryGet()?->getActivityLogThrottleMinutes()
            ?? (int) config('peek.activity_log.throttle_minutes', 10);

        if ($minutes <= 0) {
            return true;
        }

        // Cache::add() is atomic: the first writer wins the window.
        return Cache::add($this->throttleKey($record, $user), true, now()->addMinutes($minutes));
    }

    protected function throttleKey(Model $record, Authenticatable $user): string
    {
        return implode(':', [
            'peek', 'log',
            $user->getAuthIdentifier(),
            $record->getMorphClass(),
            $record->getKey(),
        ]);
    }
}
