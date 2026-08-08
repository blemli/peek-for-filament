<?php

namespace Blemli\Peek\Attachment;

use Filament\Tables\Columns\Column;
use Illuminate\Database\Eloquent\Model;

class AttachesPeekToColumns
{
    protected static bool $registered = false;

    public static function register(): void
    {
        if (static::$registered) {
            return;
        }

        static::$registered = true;

        Column::configureUsing(function (Column $column): void {
            $column->extraCellAttributes(
                fn (?Model $record): array => PeekTarget::forColumn($column, $record),
                merge: true,
            );
        });
    }
}
