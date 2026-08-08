<?php

namespace Blemli\Peek\Routing;

use Filament\Panel;
use Illuminate\Database\Eloquent\Model;

class ResolvedPeekTarget
{
    /**
     * @param  class-string  $resource
     */
    public function __construct(
        public readonly Model $record,
        public readonly string $resource,
        public readonly Panel $panel,
    ) {}
}
