<?php

namespace Blemli\Peek\Tests\Fixtures;

use Blemli\Peek\PeekPlugin;
use Blemli\Peek\Tests\Fixtures\Resources\AuthorResource;
use Blemli\Peek\Tests\Fixtures\Resources\PostResource;
use Filament\Panel;
use Filament\PanelProvider;

class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->resources([
                PostResource::class,
                AuthorResource::class,
            ])
            ->plugin(PeekPlugin::make());
    }
}
