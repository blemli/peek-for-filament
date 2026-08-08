<?php

namespace Blemli\Peek\Macros;

use Blemli\Peek\Attachment\PeekTarget;
use Filament\Infolists\Components\Entry;
use Filament\Tables\Columns\Column;
use Illuminate\Database\Eloquent\Model;

class PeekMacros
{
    protected static bool $registered = false;

    public static function register(): void
    {
        if (static::$registered) {
            return;
        }

        static::$registered = true;

        // Macros are registered from an instance context so their closures can
        // be bound to the column/entry they are called on.
        (new static)->registerMacros();
    }

    protected function registerMacros(): void
    {
        Column::macro('noPeeking', function (): Column {
            /** @var Column $this */
            return $this->extraCellAttributes(['data-peek-off' => 'true'], merge: true);
        });

        Column::macro('peekable', function (mixed $target = null): Column {
            /** @var Column $this */
            return $this->extraCellAttributes(
                fn (?Model $record): array => PeekTarget::forced($target, $record, $this),
                merge: true,
            );
        });

        Entry::macro('noPeeking', function (): Entry {
            /** @var Entry $this */
            return $this->extraEntryWrapperAttributes(['data-peek-off' => 'true'], merge: true);
        });

        Entry::macro('peekable', function (mixed $target = null): Entry {
            /** @var Entry $this */
            return $this->extraEntryWrapperAttributes(
                fn (?Model $record): array => PeekTarget::forced($target, $record, $this),
                merge: true,
            );
        });
    }
}
