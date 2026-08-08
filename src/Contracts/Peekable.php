<?php

namespace Blemli\Peek\Contracts;

use Blemli\Peek\Content\PeekData;

interface Peekable
{
    /**
     * A custom Blade view for this model's peek card, or null for the default card.
     * The view receives $record, $peek (PeekData) and $level.
     */
    public function getPeekView(): ?string;

    /**
     * Adjust the resolved peek data before it is rendered.
     */
    public function toPeek(PeekData $peek): PeekData;
}
