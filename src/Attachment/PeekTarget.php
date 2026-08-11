<?php

namespace Blemli\Peek\Attachment;

use Blemli\Peek\Authorization\PeekAuthorizer;
use Blemli\Peek\Support\PeekToken;
use Closure;
use Filament\Infolists\Components\Entry;
use Filament\Tables\Columns\Column;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * All contact with Filament's relationship APIs lives here, so a rename in a
 * future Filament minor is a one-file fix.
 */
class PeekTarget
{
    /**
     * @return array<string, string>
     */
    public static function forColumn(Column $column, Model | array | null $record): array
    {
        try {
            if (! $record instanceof Model) {
                return [];
            }

            if (! $column->hasRelationship($record) || $column->hasMultipleRelationship($record)) {
                return [];
            }

            $related = $column->getRelationshipResults($record)[0] ?? null;

            return static::attributesFor($related);
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return array<string, string>
     */
    public static function forEntry(Entry $entry, Model | array | null $record): array
    {
        try {
            if (! $record instanceof Model) {
                return [];
            }

            if (! $entry->hasStateRelationship($record) || $entry->hasMultipleStateRelationship($record)) {
                return [];
            }

            $related = $entry->getStateRelationshipResults($record)[0] ?? null;

            return static::attributesFor($related);
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * For ->peekable(): an explicit target, or fall back to relationship detection.
     *
     * @return array<string, string>
     */
    public static function forced(mixed $target, Model | array | null $record, Column | Entry $component): array
    {
        try {
            if ($target instanceof Closure) {
                $target = $target($record);
            }

            if (is_array($target) && count($target) === 2) {
                [$model, $key] = $target;

                if (is_string($model) && is_subclass_of($model, Model::class)) {
                    $target = $model::query()->whereKey($key)->first();
                }
            }

            if ($target instanceof Model) {
                return static::attributesFor($target);
            }

            return $component instanceof Column
                ? static::forColumn($component, $record)
                : static::forEntry($component, $record);
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return array<string, string>
     */
    protected static function attributesFor(?Model $related): array
    {
        if (! $related || ! $related->exists) {
            return [];
        }

        if (! app(PeekAuthorizer::class)->isPeekable($related)) {
            return [];
        }

        return ['data-peek' => PeekToken::make($related)];
    }
}
