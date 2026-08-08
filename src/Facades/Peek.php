<?php

namespace Blemli\Peek\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Blemli\Peek\Peek
 */
class Peek extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Blemli\Peek\Peek::class;
    }
}
