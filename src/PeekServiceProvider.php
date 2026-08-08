<?php

namespace Blemli\Peek;

use Blemli\Peek\Activity\PeekActivityLogger;
use Blemli\Peek\Authorization\PeekAuthorizer;
use Blemli\Peek\Commands\UninstallCommand;
use Blemli\Peek\Content\HeuristicContentSource;
use Blemli\Peek\Content\PeekContent;
use Blemli\Peek\Content\ResourceContentSource;
use Blemli\Peek\Routing\RecordUrlResolver;
use Blemli\Peek\Testing\TestsPeek;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Livewire\Features\SupportTesting\Testable;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PeekServiceProvider extends PackageServiceProvider
{
    public static string $name = 'peek';

    public static string $viewNamespace = 'peek';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasConfigFile()
            ->hasViews(static::$viewNamespace)
            ->hasTranslations()
            ->hasCommands([UninstallCommand::class])
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->publishConfigFile()
                    ->endWith(fn (InstallCommand $command) => $this->promptAttachToPanelProvider($command));
            });
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(PeekAuthorizer::class);
        $this->app->singleton(RecordUrlResolver::class);
        $this->app->singleton(PeekActivityLogger::class);
        $this->app->singleton(ResourceContentSource::class);
        $this->app->singleton(HeuristicContentSource::class);
        $this->app->singleton(PeekContent::class);
        $this->app->singleton(Peek::class);
        $this->app->singleton(PeekPlugin::class);
    }

    public function packageBooted(): void
    {
        FilamentAsset::register($this->getAssets(), $this->getAssetPackageName());

        if (class_exists(Testable::class)) {
            Testable::mixin(new TestsPeek);
        }
    }

    protected function getAssetPackageName(): string
    {
        return 'blemli/peek-for-filament';
    }

    /**
     * @return array<int, Asset>
     */
    protected function getAssets(): array
    {
        return [
            Js::make('peek', __DIR__ . '/../resources/dist/peek.js'),
            Css::make('peek', __DIR__ . '/../resources/css/peek.css'),
        ];
    }

    protected function promptAttachToPanelProvider(InstallCommand $command): void
    {
        $candidates = $this->findPanelProviderCandidates();

        if ($candidates === []) {
            $this->printAttachSnippet($command, 'No PanelProvider found under app/Providers/Filament/.');

            return;
        }

        if (count($candidates) === 1) {
            $path = $candidates[0];
        } else {
            $choice = $command->choice(
                'Multiple PanelProviders found. Which one should Peek attach to?',
                [...$candidates, '(none — skip and print snippet)'],
            );

            if ($choice === '(none — skip and print snippet)') {
                $this->printAttachSnippet($command, 'Skipped.');

                return;
            }

            $path = (string) $choice;
        }

        $contents = (string) file_get_contents($path);

        if (str_contains($contents, 'PeekPlugin')) {
            $command->info("PeekPlugin is already attached in {$path}.");

            return;
        }

        $patched = $this->insertPluginCall($contents);

        if ($patched === null) {
            $this->printAttachSnippet(
                $command,
                "Could not safely patch {$path} — the panel chain looks unusual.",
            );

            return;
        }

        $relative = $this->relativePath($path);

        if (! $command->confirm("Attach PeekPlugin to {$relative}?", true)) {
            $this->printAttachSnippet($command, 'Skipped.');

            return;
        }

        file_put_contents($path, $patched);
        $command->info("Peek attached in {$relative}.");
    }

    /**
     * @return array<int, string>
     */
    protected function findPanelProviderCandidates(): array
    {
        $dir = app_path('Providers/Filament');

        if (! is_dir($dir)) {
            return [];
        }

        $files = glob($dir . DIRECTORY_SEPARATOR . '*PanelProvider.php') ?: [];

        return array_values(array_filter($files, is_file(...)));
    }

    protected function insertPluginCall(string $contents): ?string
    {
        // Match the panel(...) method up to the trailing `;` of its return chain,
        // then insert the plugin call before that semicolon.
        $pattern = '/(public function panel\(Panel \$panel\)\s*:\s*Panel\s*\{\s*return \$panel(?:[^;]+))(;)/s';

        $replaced = preg_replace_callback(
            $pattern,
            static function (array $matches): string {
                $chain = rtrim($matches[1]);
                $indent = '            ';

                return "{$chain}\n{$indent}->plugin(\\Blemli\\Peek\\PeekPlugin::make())" . $matches[2];
            },
            $contents,
            1,
            $count,
        );

        if ($count === 0 || $replaced === null) {
            return null;
        }

        return $replaced;
    }

    protected function printAttachSnippet(InstallCommand $command, string $reason): void
    {
        $command->warn($reason);
        $command->line('');
        $command->line('Add this to your PanelProvider::panel() method:');
        $command->line('');
        $command->line('    use Blemli\\Peek\\PeekPlugin;');
        $command->line('');
        $command->line('    return $panel');
        $command->line('        // ... existing config ...');
        $command->line('        ->plugin(PeekPlugin::make());');
        $command->line('');
    }

    protected function relativePath(string $path): string
    {
        $base = base_path();

        if (str_starts_with($path, $base)) {
            return ltrim(substr($path, strlen($base)), DIRECTORY_SEPARATOR);
        }

        return $path;
    }
}
