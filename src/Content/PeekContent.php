<?php

namespace Blemli\Peek\Content;

use Blemli\Peek\Contracts\Peekable;
use Blemli\Peek\PeekPlugin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

class PeekContent
{
    public function __construct(
        protected ResourceContentSource $resourceSource,
        protected HeuristicContentSource $heuristicSource,
    ) {}

    /**
     * Render the peek card HTML for a record, or null when there is nothing to show.
     */
    public function resolve(Model $record, int $level = 0): ?string
    {
        try {
            $depth = PeekPlugin::tryGet()?->getPeekDepth() ?? max(1, (int) config('peek.depth', 1));
            $canNest = ($level + 1) < $depth;

            $peek = PeekData::make();
            $this->resourceSource->apply($peek, $record);
            $this->heuristicSource->apply($peek, $record);

            if ($record instanceof Peekable) {
                $peek = $record->toPeek($peek);
            }

            $view = $this->customView($record);

            if (! $view && $peek->isEmpty()) {
                return null;
            }

            return view($view ?? 'peek::content.default', [
                'record' => $record,
                'peek' => $peek,
                'level' => $level,
                'canNest' => $canNest,
            ])->render();
        } catch (Throwable) {
            return null;
        }
    }

    protected function customView(Model $record): ?string
    {
        $registered = PeekPlugin::tryGet()?->getPeekViews()[$record::class] ?? null;

        if ($registered && view()->exists($registered)) {
            return $registered;
        }

        if ($record instanceof Peekable && filled($record->getPeekView()) && view()->exists($record->getPeekView())) {
            return $record->getPeekView();
        }

        $conventional = 'peek.' . Str::kebab(class_basename($record));

        return view()->exists($conventional) ? $conventional : null;
    }
}
