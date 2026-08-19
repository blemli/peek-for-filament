<?php

use Blemli\Peek\Commands\UninstallCommand;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\File;

function fakePublishedArtifacts(): array
{
    $paths = [
        config_path('peek.php') => '<?php return [];',
        resource_path('views/vendor/peek/peek-box.blade.php') => '<div></div>',
        lang_path('vendor/peek/en/peek.php') => '<?php return [];',
        resource_path('lang/vendor/peek/de/peek.php') => '<?php return [];',
        public_path('css/blemli/peek-for-filament/peek.css') => '/* */',
        public_path('js/blemli/peek-for-filament/peek.js') => '// ',
    ];

    foreach ($paths as $path => $contents) {
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $contents);
    }

    return array_keys($paths);
}

// The fakes land in the shared testbench skeleton — always clean up, even on
// failure, so a red test doesn't poison the rest of the suite.
afterEach(function () {
    File::delete(config_path('peek.php'));

    foreach ([
        resource_path('views/vendor/peek'),
        lang_path('vendor/peek'),
        resource_path('lang/vendor/peek'),
        public_path('css/blemli'),
        public_path('js/blemli'),
    ] as $dir) {
        File::deleteDirectory($dir);
    }
});

it('removes every published artifact and prunes empty vendor dirs', function () {
    $paths = fakePublishedArtifacts();

    $this->artisan('peek:uninstall', ['--force' => true])->assertSuccessful();

    foreach ($paths as $path) {
        expect(File::exists($path))->toBeFalse();
    }

    expect(File::isDirectory(public_path('css/blemli')))->toBeFalse()
        ->and(File::isDirectory(public_path('js/blemli')))->toBeFalse();
});

it('reports that there are no tables to drop', function () {
    fakePublishedArtifacts();

    $this->artisan('peek:uninstall', ['--force' => true])
        ->expectsOutputToContain('No database tables to drop')
        ->assertSuccessful();
});

it('keeps console output English regardless of the app locale', function () {
    app()->setLocale('de');
    fakePublishedArtifacts();

    $this->artisan('peek:uninstall', ['--force' => true])
        ->expectsOutputToContain('The following published files will be removed:')
        ->expectsOutputToContain('composer remove blemli/peek-for-filament')
        ->assertSuccessful();
});

it('succeeds when nothing was ever published', function () {
    $this->artisan('peek:uninstall', ['--force' => true])
        ->expectsOutputToContain('nothing found')
        ->assertSuccessful();
});

function fakePanelProvider(): string
{
    $provider = app_path('Providers/Filament/AdminPanelProvider.php');

    File::ensureDirectoryExists(dirname($provider));
    File::put($provider, <<<'FIXTURE'
        <?php

        namespace App\Providers\Filament;

        use Blemli\Peek\PeekPlugin;

        class AdminPanelProvider
        {
            public function panel($panel)
            {
                return $panel->plugin(PeekPlugin::make()->logActivity());
            }
        }
        FIXTURE);

    return $provider;
}

it('points at the registration and waits until the developer removed it', function () {
    $provider = fakePanelProvider();

    $this->artisan('peek:uninstall')
        ->expectsConfirmation('Remove these files?', 'yes')
        ->expectsOutputToContain('PeekPlugin is still registered in ' . $provider . ':5')
        ->expectsConfirmation('Removed it?', 'yes')
        // Still there on the re-check, so it asks again.
        ->expectsOutputToContain('or the panel will crash after')
        ->expectsConfirmation('Removed it?', 'no')
        ->expectsConfirmation('Run `composer remove blemli/peek-for-filament` now?', 'no')
        ->assertSuccessful();

    // The provider is the developer's code — peek never rewrites it.
    expect(File::get($provider))->toContain('PeekPlugin::make()');

    File::deleteDirectory(app_path('Providers/Filament'));
});

it('stops asking once the registration is gone', function () {
    // The developer edits the provider between the two prompts — stub the scan
    // rather than the filesystem so the loop itself is what gets tested.
    $command = new class extends UninstallCommand
    {
        /** @var array<array<string>> */
        public array $scans = [['/app/Providers/Filament/AdminPanelProvider.php:5'], []];

        protected function findPluginRegistrations(): array
        {
            return array_shift($this->scans) ?? [];
        }
    };

    $command->setLaravel(app());
    app(Kernel::class)->registerCommand($command);

    $this->artisan('peek:uninstall')
        ->expectsConfirmation('Remove these files?', 'yes')
        ->expectsOutputToContain('PeekPlugin is still registered in')
        ->expectsConfirmation('Removed it?', 'yes')
        ->expectsOutputToContain('No PeekPlugin registration left')
        ->expectsConfirmation('Run `composer remove blemli/peek-for-filament` now?', 'no')
        ->assertSuccessful();
});

it('never blocks on prompts with --force', function () {
    fakePanelProvider();

    $this->artisan('peek:uninstall', ['--force' => true])
        ->expectsOutputToContain('PeekPlugin is still registered in')
        ->assertSuccessful();

    File::deleteDirectory(app_path('Providers/Filament'));
});
