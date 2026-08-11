<?php

namespace Blemli\Peek\Macros;

use Blemli\Peek\Attachment\PeekTarget;
use Closure;
use Filament\Tables\Columns\Column;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Column
 */
final class PeekColumnMacros
{
    public function noPeeking(): Closure
    {
        return function () {
            return $this->extraCellAttributes(['data-peek-off' => 'true'], merge: true);
        };
    }

    public function peekable(): Closure
    {
        return function (mixed $target = null) {
            return $this->extraCellAttributes(
                fn (Model | array | null $record): array => PeekTarget::forced($target, $record, $this),
                merge: true,
            );
        };
    }
}
