<?php

namespace Blemli\Peek\Authorization;

use Blemli\Peek\Contracts\NoPeeking;
use Blemli\Peek\PeekPlugin;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Throwable;

use function Filament\get_authorization_response;

class PeekAuthorizer
{
    /**
     * Any failure means "no peek" — silently, so a denial is indistinguishable
     * from there being nothing to show.
     */
    public function canPeek(Model $record, ?string $resource = null): bool
    {
        try {
            if (! $this->isPeekable($record)) {
                return false;
            }

            if (! Filament::auth()->user()) {
                return false;
            }

            $resource ??= Filament::getModelResource($record);

            if ($resource && ! $this->isPeekable($resource)) {
                return false;
            }

            // A dedicated `peek` policy ability wins over `view` in both directions.
            $policy = Gate::getPolicyFor($record);

            if (filled($policy) && method_exists($policy, 'peek')) {
                return get_authorization_response('peek', $record)->allowed();
            }

            if ($resource) {
                return $resource::canView($record);
            }

            // No resource: mirror Filament's own default (no policy => allow,
            // honoring Gate::before and strict authorization mode).
            return get_authorization_response('view', $record)->allowed();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Structural check: is this model/resource ever allowed to be previewed?
     *
     * @param  Model|class-string  $modelOrResource
     */
    public function isPeekable(Model | string $modelOrResource): bool
    {
        try {
            $class = $modelOrResource instanceof Model ? $modelOrResource::class : $modelOrResource;

            if (is_a($class, NoPeeking::class, true)) {
                return false;
            }

            $noPeekingList = PeekPlugin::tryGet()?->getNoPeekingList()
                ?? [
                    ...(array) config('peek.no_peeking.models', []),
                    ...(array) config('peek.no_peeking.resources', []),
                ];

            if (in_array($class, $noPeekingList, true)) {
                return false;
            }

            if ($modelOrResource instanceof Model || is_a($class, Model::class, true)) {
                $resource = Filament::getModelResource($class);

                if ($resource && (is_a($resource, NoPeeking::class, true) || in_array($resource, $noPeekingList, true))) {
                    return false;
                }
            }

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
