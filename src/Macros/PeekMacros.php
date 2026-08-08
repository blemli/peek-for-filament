<?php

namespace Blemli\Peek\Macros;

use Filament\Infolists\Components\Entry;
use Filament\Tables\Columns\Column;

final class PeekMacros
{
    public static function register(): void
    {
        // Macros live in static class state and survive app rebuilds;
        // re-registering just overwrites them, so no container guard needed.
        if (Column::hasMacro('noPeeking')) {
            return;
        }

        Column::mixin(new PeekColumnMacros);
        Entry::mixin(new PeekEntryMacros);
    }
}
