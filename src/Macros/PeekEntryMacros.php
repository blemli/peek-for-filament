<?php

namespace Blemli\Peek\Macros;

use Blemli\Peek\Attachment\PeekTarget;
use Closure;
use Filament\Infolists\Components\Entry;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Entry
 */
final class PeekEntryMacros
{
    public function noPeeking(): Closure
    {
        return function () {
            return $this->extraEntryWrapperAttributes(['data-peek-off' => 'true'], merge: true);
        };
    }

    public function peekable(): Closure
    {
        return function (mixed $target = null) {
            return $this->extraEntryWrapperAttributes(
                fn (Model | array | null $record): array => PeekTarget::forced($target, $record, $this),
                merge: true,
            );
        };
    }
}
