<?php

namespace Blemli\Peek\Content;

final class PeekField
{
    public function __construct(
        public string $type, // text | address | link | money
        public ?string $label = null,
        public mixed $value = null,
    ) {}

    public static function make(string $type, ?string $label = null, mixed $value = null): self
    {
        return new self($type, $label, $value);
    }
}
