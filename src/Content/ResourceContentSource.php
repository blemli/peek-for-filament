<?php

namespace Blemli\Peek\Content;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Fills peek data from the record's Filament resource, when one exists.
 */
class ResourceContentSource
{
    public function apply(PeekData $peek, Model $record): void
    {
        try {
            $resource = Filament::getModelResource($record);

            if (! $resource) {
                return;
            }

            $peek->kicker((string) $resource::getModelLabel());

            if (filled($resource::getRecordTitleAttribute())) {
                $title = $resource::getRecordTitle($record);
                $peek->title(strip_tags((string) $title));
            }

            $peek->url($this->recordUrl($resource, $record));
        } catch (Throwable) {
            // Fall through to heuristics.
        }
    }

    /**
     * @param  class-string  $resource
     */
    protected function recordUrl(string $resource, Model $record): ?string
    {
        foreach (['view' => 'canView', 'edit' => 'canEdit'] as $page => $ability) {
            try {
                if ($resource::{$ability}($record)) {
                    return $resource::getUrl($page, ['record' => $record]);
                }
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }
}
