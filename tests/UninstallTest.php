<?php

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
