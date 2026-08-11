<?php

namespace Blemli\Peek\Attachment;

use Filament\Tables\Columns\Column;
use Illuminate\Database\Eloquent\Model;

class AttachesPeekToColumns
{
    public static function register(): void
    {
        // Container-bound guard: Filament's ComponentManager is app-scoped, so
        // the registration must repeat for every fresh app (tests, Octane).
        if (app()->bound('peek.attached-columns')) {
            return;
        }

        app()->instance('peek.attached-columns', true);

        Column::configureUsing(function (Column $column): void {
            $column->extraCellAttributes(
                fn (Model | array | null $record): array => PeekTarget::forColumn($column, $record),
                merge: true,
            );
        });
    }
}
