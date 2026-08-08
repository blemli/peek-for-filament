<?php

namespace Blemli\Peek;

use Blemli\Peek\Activity\PeekActivityLogger;
use Blemli\Peek\Authorization\PeekAuthorizer;
use Blemli\Peek\Routing\RecordUrlResolver;
use Blemli\Peek\Routing\ResolvedPeekTarget;
use Blemli\Peek\Support\PeekToken;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class Peek
{
    public function __construct(
        protected PeekAuthorizer $authorizer,
        protected RecordUrlResolver $urlResolver,
        protected PeekActivityLogger $activityLogger,
    ) {}

    public function plugin(): ?PeekPlugin
    {
        return PeekPlugin::tryGet();
    }

    public function isEnabled(): bool
    {
        return $this->plugin()?->isEnabled() ?? (bool) config('peek.enabled', true);
    }

    /**
     * Resolve a "token:..." or "url:..." peek target to an authorized record, or null.
     */
    public function resolveAuthorized(string $target): ?Model
    {
        try {
            $record = $this->resolveTarget($target);

            if (! $record || ! $this->canPeek($record)) {
                return null;
            }

            return $record;
        } catch (Throwable) {
            return null;
        }
    }

    public function canPeek(Model $record, ?string $resource = null): bool
    {
        return $this->authorizer->canPeek($record, $resource);
    }

    public function isModelPeekable(Model | string $model): bool
    {
        return $this->authorizer->isPeekable($model);
    }

    public function logPeek(Model $record): void
    {
        if (! ($this->plugin()?->shouldLogActivity() ?? (bool) config('peek.activity_log.enabled', false))) {
            return;
        }

        $this->activityLogger->log($record);
    }

    protected function resolveTarget(string $target): ?Model
    {
        if (str_starts_with($target, 'token:')) {
            $parsed = PeekToken::parse(substr($target, strlen('token:')));

            if (! $parsed) {
                return null;
            }

            [$model, $key] = $parsed;

            return $model::query()->whereKey($key)->first();
        }

        if (str_starts_with($target, 'url:')) {
            return $this->urlResolver->resolve(substr($target, strlen('url:')))?->record;
        }

        return null;
    }

    public function resolveUrl(string $url): ?ResolvedPeekTarget
    {
        return $this->urlResolver->resolve($url);
    }
}
