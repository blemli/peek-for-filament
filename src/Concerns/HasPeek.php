<?php

namespace Blemli\Peek\Concerns;

use Blemli\Peek\Content\PeekData;

/**
 * Default implementation of the Peekable contract.
 */
trait HasPeek
{
    public function getPeekView(): ?string
    {
        return null;
    }

    public function toPeek(PeekData $peek): PeekData
    {
        return $peek;
    }
}
