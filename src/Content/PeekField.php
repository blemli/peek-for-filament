<?php

namespace Blemli\Peek\Content;

class PeekField
{
    public function __construct(
        public string $type, // text | address | link | money | date
        public ?string $label = null,
        public mixed $value = null,
    ) {}

    public static function make(string $type, ?string $label = null, mixed $value = null): static
    {
        return new static($type, $label, $value);
    }
}
