<?php

namespace Blemli\Peek\Attachment;

use Filament\Infolists\Components\Entry;
use Illuminate\Database\Eloquent\Model;

class AttachesPeekToEntries
{
    public static function register(): void
    {
        // Container-bound guard: see AttachesPeekToColumns.
        if (app()->bound('peek.attached-entries')) {
            return;
        }

        app()->instance('peek.attached-entries', true);

        Entry::configureUsing(function (Entry $entry): void {
            $entry->extraEntryWrapperAttributes(
                fn (Model | array | null $record): array => PeekTarget::forEntry($entry, $record),
                merge: true,
            );
        });
    }
}
