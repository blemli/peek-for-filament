<?php

namespace Blemli\Peek\Routing;

use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Throwable;

class RecordUrlResolver
{
    protected const MAX_URL_LENGTH = 2048;

    /**
     * Resolve an anchor href from inside the panel to a record, or null.
     *
     * The URL is hostile input (it arrives via a Livewire request): only
     * same-origin URLs pointing at a view/edit record page of the current
     * panel ever resolve. The route is matched, never dispatched.
     */
    public function resolve(string $url, ?Panel $panel = null): ?ResolvedPeekTarget
    {
        try {
            $panel ??= Filament::getCurrentOrDefaultPanel();

            if (! $panel) {
                return null;
            }

            $path = $this->sanitize($url, $panel);

            if ($path === null) {
                return null;
            }

            $request = Request::create($path, 'GET');
            $route = app('router')->getRoutes()->match($request);

            $routeName = (string) $route->getName();
            $namePrefix = "filament.{$panel->getId()}.resources.";

            if (! str_starts_with($routeName, $namePrefix)) {
                return null;
            }

            if (! str_ends_with($routeName, '.view') && ! str_ends_with($routeName, '.edit')) {
                return null;
            }

            $route->bind($request);

            $key = $route->parameter('record');

            if (! is_string($key) && ! is_int($key)) {
                return null;
            }

            $resource = $this->resourceForRouteName($routeName, $namePrefix, $panel);

            if (! $resource) {
                return null;
            }

            $record = $resource::resolveRecordRouteBinding($key);

            if (! $record) {
                return null;
            }

            return new ResolvedPeekTarget($record, $resource, $panel);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Returns the URL's path if it is same-origin and inside the panel, else null.
     */
    protected function sanitize(string $url, Panel $panel): ?string
    {
        if (strlen($url) > static::MAX_URL_LENGTH) {
            return null;
        }

        $parts = parse_url($url);

        if ($parts === false) {
            return null;
        }

        if (isset($parts['scheme']) && ! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return null;
        }

        if (isset($parts['host'])) {
            $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
            $allowedHosts = array_filter([$appHost, ...$panel->getDomains()]);

            if (! in_array(strtolower($parts['host']), array_map(strtolower(...), $allowedHosts), true)) {
                return null;
            }
        }

        $path = $parts['path'] ?? '';

        if ($path === '' || ! str_starts_with($path, '/')) {
            return null;
        }

        $panelPath = trim($panel->getPath(), '/');

        if ($panelPath !== '' && ! str_starts_with(trim($path, '/') . '/', $panelPath . '/')) {
            return null;
        }

        return $path;
    }

    /**
     * @return class-string|null
     */
    protected function resourceForRouteName(string $routeName, string $namePrefix, Panel $panel): ?string
    {
        // Compare against each resource's own route base name; longest prefix
        // wins so nested resources (parent-prefixed names) match correctly.
        // Filament v5 already includes the "filament.{panel}." prefix in the
        // base name, v4 does not — normalize to the fully-prefixed form.
        $fullPrefix = "filament.{$panel->getId()}.";

        $match = null;
        $matchLength = 0;

        foreach ($panel->getResources() as $resource) {
            $baseName = $resource::getRouteBaseName($panel);

            if (! str_starts_with($baseName, $fullPrefix)) {
                $baseName = $fullPrefix . $baseName;
            }

            if (! str_starts_with($routeName, $baseName . '.')) {
                continue;
            }

            if (strlen($baseName) > $matchLength) {
                $match = $resource;
                $matchLength = strlen($baseName);
            }
        }

        return $match;
    }
}
