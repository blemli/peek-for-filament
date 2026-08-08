<?php

namespace Blemli\Peek\Commands;

use Illuminate\Console\Command;

class PeekCommand extends Command
{
    public $signature = 'peek-for-filament';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
