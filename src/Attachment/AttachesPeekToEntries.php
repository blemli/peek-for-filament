<?php

namespace Blemli\Peek\Attachment;

use Filament\Infolists\Components\Entry;
use Illuminate\Database\Eloquent\Model;

class AttachesPeekToEntries
{
    protected static bool $registered = false;

    public static function register(): void
    {
        if (static::$registered) {
            return;
        }

        static::$registered = true;

        Entry::configureUsing(function (Entry $entry): void {
            $entry->extraEntryWrapperAttributes(
                fn (?Model $record): array => PeekTarget::forEntry($entry, $record),
                merge: true,
            );
        });
    }
}
