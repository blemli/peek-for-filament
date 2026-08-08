<?php

namespace Blemli\Peek\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Finder\Finder;

class UninstallCommand extends Command
{
    public $signature = 'peek:uninstall {--force : Skip all confirmation prompts}';

    public $description = 'Remove everything peek:install and filament:assets published';

    // Console output is deliberately hardcoded English — it must not depend on the app locale.
    public function handle(): int
    {
        $paths = $this->publishedPaths();
        $existing = array_filter($paths, fn (string $path): bool => File::exists($path));

        $this->line('The following published files will be removed:');
        $this->line('');

        if ($existing === []) {
            $this->line('  (nothing found — no published files on disk)');
        }

        foreach ($existing as $path) {
            $this->line('  - ' . $path);
        }

        $this->line('');
        $this->line('No database tables to drop: peek ships no migrations.');
        $this->line('');

        if (! $this->option('force') && ! $this->confirm('Remove these files?', true)) {
            $this->warn('Aborted. Nothing was removed.');

            return self::FAILURE;
        }

        foreach ($existing as $path) {
            File::isDirectory($path) ? File::deleteDirectory($path) : File::delete($path);
            $this->info('Removed ' . $path);
        }

        $this->pruneEmptyBlemliDirs();

        $registrations = $this->findPluginRegistrations();

        foreach ($registrations as $hit) {
            $this->warn("PeekPlugin is still registered in {$hit} — remove the ->plugin(...) call,");
            $this->warn('or the panel will crash after `composer remove blemli/peek-for-filament`.');
        }

        $this->offerComposerRemove($registrations);

        $this->info('Peek uninstalled.');

        return self::SUCCESS;
    }

    /**
     * @return array<string>
     */
    protected function publishedPaths(): array
    {
        return [
            config_path('peek.php'),
            resource_path('views/vendor/peek'),
            lang_path('vendor/peek'),
            resource_path('lang/vendor/peek'),
            public_path('css/blemli/peek-for-filament'),
            public_path('js/blemli/peek-for-filament'),
        ];
    }

    protected function pruneEmptyBlemliDirs(): void
    {
        foreach ([public_path('css/blemli'), public_path('js/blemli')] as $dir) {
            if (File::isDirectory($dir) && File::files($dir) === [] && File::directories($dir) === []) {
                File::deleteDirectory($dir);
                $this->info('Removed empty ' . $dir);
            }
        }
    }

    /**
     * @return array<string> "path:line" hits
     */
    protected function findPluginRegistrations(): array
    {
        if (! is_dir(app_path('Providers'))) {
            return [];
        }

        $hits = [];

        $finder = (new Finder)
            ->files()
            ->in(app_path('Providers'))
            ->name('*.php')
            ->contains('PeekPlugin');

        foreach ($finder as $file) {
            foreach (explode("\n", $file->getContents()) as $lineNumber => $line) {
                if (str_contains($line, 'PeekPlugin')) {
                    $hits[] = $file->getRealPath() . ':' . ($lineNumber + 1);
                }
            }
        }

        return $hits;
    }

    /**
     * @param  array<string>  $registrations
     */
    protected function offerComposerRemove(array $registrations): void
    {
        if ($this->option('force')) {
            $this->line('Run `composer remove blemli/peek-for-filament` to remove the package.');

            return;
        }

        $default = $registrations === [];

        if (! $this->confirm('Run `composer remove blemli/peek-for-filament` now?', $default)) {
            $this->line('Run `composer remove blemli/peek-for-filament` when you are ready.');

            return;
        }

        $result = Process::forever()->run(
            ['composer', 'remove', 'blemli/peek-for-filament'],
            fn (string $type, string $output) => $this->output->write($output),
        );

        if (! $result->successful()) {
            $this->error('composer remove failed — run it manually.');
        }
    }
}
