<?php

namespace Blemli\Peek\Content;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Guesses the most important fields of a record from its attribute names.
 */
class HeuristicContentSource
{
    protected const MAX_FIELDS = 5;

    protected const MAX_CHIPS = 3;

    public function apply(PeekData $peek, Model $record): void
    {
        try {
            $attributes = $this->presentableAttributes($record);

            $this->fillImage($peek, $record, $attributes);
            $this->fillTitle($peek, $record, $attributes);
            $this->fillSecondary($peek, $attributes);
            $this->fillDescription($peek, $attributes);
            $this->fillAddress($peek, $attributes);
            $this->fillFields($peek, $attributes);
            $this->fillChips($peek, $attributes);
            $this->fillPerson($peek, $record);
        } catch (Throwable) {
            // A half-filled card is better than none.
        }
    }

    /**
     * @return array<string, string>
     */
    protected function presentableAttributes(Model $record): array
    {
        $presentable = [];

        foreach ($record->attributesToArray() as $key => $value) {
            if ($value instanceof \Stringable) {
                $value = (string) $value;
            }

            if ((is_string($value) && trim($value) !== '') || is_int($value) || is_float($value)) {
                $presentable[strtolower($key)] = (string) $value;
            }
        }

        return $presentable;
    }

    /**
     * @param  array<string, string>  $attributes
     */
    protected function fillImage(PeekData $peek, Model $record, array &$attributes): void
    {
        if (filled($peek->image)) {
            return;
        }

        $avatarKeys = ['avatar', 'avatar_url', 'photo', 'profile_photo_path', 'portrait'];
        $bannerKeys = ['banner', 'cover', 'cover_image', 'header_image'];
        $autoKeys = ['image', 'image_url', 'picture', 'logo', 'thumbnail', 'thumb'];

        foreach (['avatar' => $avatarKeys, 'banner' => $bannerKeys, 'auto' => $autoKeys] as $shape => $keys) {
            foreach ($keys as $key) {
                if ($url = $this->imageUrl($attributes[$key] ?? null)) {
                    unset($attributes[$key]);
                    $peek->image($url, $shape);

                    return;
                }
            }
        }

        if (method_exists($record, 'getFilamentAvatarUrl') && ($url = rescue(fn () => $record->getFilamentAvatarUrl(), report: false))) {
            $peek->image($url, 'avatar');

            return;
        }

        if (method_exists($record, 'getFirstMediaUrl') && ($url = rescue(fn () => $record->getFirstMediaUrl(), report: false))) {
            $peek->image($url);
        }
    }

    protected function imageUrl(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (Str::startsWith($value, ['http://', 'https://', '/', 'data:image/'])) {
            return $value;
        }

        // Looks like a storage path.
        if (preg_match('/\.(jpe?g|png|gif|webp|avif|svg)$/i', $value)) {
            return rescue(fn (): string => Storage::url($value), report: false);
        }

        return null;
    }

    /**
     * @param  array<string, string>  $attributes
     */
    protected function fillTitle(PeekData $peek, Model $record, array &$attributes): void
    {
        if (blank($peek->title)) {
            foreach (['title', 'name', 'full_name', 'label', 'headline', 'subject'] as $key) {
                if (filled($attributes[$key] ?? null)) {
                    $peek->title($attributes[$key]);
                    unset($attributes[$key]);

                    break;
                }
            }
        }

        if (blank($peek->title) && method_exists($record, 'getFilamentName')) {
            $peek->title(rescue(fn () => $record->getFilamentName(), report: false));
        }

        if (blank($peek->title)) {
            $peek->title(Str::headline(class_basename($record)) . ' #' . $record->getKey());
        } else {
            // The title matcher consumed one of these; drop synonyms so the
            // meta grid doesn't repeat the heading.
            unset($attributes['title'], $attributes['name'], $attributes['label']);
        }

        if (blank($peek->kicker)) {
            $peek->kicker(Str::headline(class_basename($record)));
        }
    }

    /**
     * @param  array<string, string>  $attributes
     */
    protected function fillSecondary(PeekData $peek, array &$attributes): void
    {
        if (filled($peek->secondary)) {
            return;
        }

        foreach (['email', 'mail', 'subtitle', 'secondary', 'role', 'position', 'job_title'] as $key) {
            if (filled($attributes[$key] ?? null)) {
                $peek->secondary($attributes[$key]);
                unset($attributes[$key]);

                return;
            }
        }
    }

    /**
     * @param  array<string, string>  $attributes
     */
    protected function fillDescription(PeekData $peek, array &$attributes): void
    {
        if (filled($peek->description)) {
            return;
        }

        foreach (['description', 'summary', 'excerpt', 'bio', 'about', 'body', 'content', 'notes'] as $key) {
            if (filled($attributes[$key] ?? null)) {
                $peek->description(Str::limit(trim(strip_tags($attributes[$key])), 120));
                unset($attributes[$key]);

                return;
            }
        }
    }

    /**
     * @param  array<string, string>  $attributes
     */
    protected function fillAddress(PeekData $peek, array &$attributes): void
    {
        $street = $attributes['street'] ?? $attributes['address'] ?? $attributes['street_address'] ?? null;
        $zip = $attributes['zip'] ?? $attributes['zip_code'] ?? $attributes['postal_code'] ?? $attributes['postcode'] ?? null;
        $city = $attributes['city'] ?? $attributes['town'] ?? null;
        $country = $attributes['country'] ?? null;

        if (blank($street) && blank($city)) {
            return;
        }

        $line = implode(', ', array_filter([
            $street,
            trim(($zip ? $zip . ' ' : '') . ($city ?? '')) ?: null,
            $country,
        ]));

        foreach (['street', 'address', 'street_address', 'zip', 'zip_code', 'postal_code', 'postcode', 'city', 'town', 'country'] as $key) {
            unset($attributes[$key]);
        }

        $peek->field(PeekField::make('address', __('peek::peek.fields.address'), $line));
    }

    /**
     * @param  array<string, string>  $attributes
     */
    protected function fillFields(PeekData $peek, array &$attributes): void
    {
        $matchers = [
            'text' => ['tel', 'phone', 'phone_number', 'mobile', 'email', 'mail'],
            'link' => ['url', 'website', 'link', 'homepage', 'domain'],
        ];

        foreach ($matchers as $type => $keys) {
            foreach ($keys as $key) {
                if (count($peek->fields) >= static::MAX_FIELDS) {
                    return;
                }

                if (filled($attributes[$key] ?? null)) {
                    $peek->field(PeekField::make($type, Str::headline($key), $attributes[$key]));
                    unset($attributes[$key]);
                }
            }
        }

        $this->fillMoney($peek, $attributes);
    }

    /**
     * @param  array<string, string>  $attributes
     */
    protected function fillMoney(PeekData $peek, array &$attributes): void
    {
        if (count($peek->fields) >= static::MAX_FIELDS) {
            return;
        }

        $currency = $attributes['currency'] ?? null;

        foreach (['price', 'amount', 'total', 'salary', 'budget'] as $key) {
            $value = $attributes[$key] ?? null;

            if (filled($value) && is_numeric($value)) {
                $formatted = number_format((float) $value, 2, '.', "'");
                $peek->field(PeekField::make(
                    'money',
                    Str::headline($key),
                    trim(strtoupper($currency ?? '') . ' ' . $formatted),
                ));
                unset($attributes[$key], $attributes['currency']);

                return;
            }
        }
    }

    /**
     * @param  array<string, string>  $attributes
     */
    protected function fillChips(PeekData $peek, array &$attributes): void
    {
        $color = $attributes['color'] ?? $attributes['colour'] ?? $attributes['hex'] ?? null;

        if (filled($color) && preg_match('/^#?[0-9a-f]{3,8}$/i', $color)) {
            $peek->chip('color', Str::start($color, '#'));
        }

        $icon = $attributes['icon'] ?? null;

        if (filled($icon) && preg_match('/^[a-z0-9\-]+(-o-)?[a-z0-9\-]*$/i', $icon) && count($peek->chips) < static::MAX_CHIPS) {
            $peek->chip('icon', $icon);
        }
    }

    protected function fillPerson(PeekData $peek, Model $record): void
    {
        if ($peek->person !== null) {
            return;
        }

        foreach (['author', 'user', 'owner', 'creator', 'created_by', 'createdBy'] as $relation) {
            $related = $record->getRelations()[$relation] ?? null;

            if ($related instanceof Model) {
                $peek->person($related, $this->personTitle($related));

                return;
            }
        }
    }

    protected function personTitle(Model $person): string
    {
        foreach (['name', 'full_name', 'title', 'email'] as $key) {
            $value = rescue(fn () => $person->getAttribute($key), report: false);

            if (filled($value) && is_string($value)) {
                return $value;
            }
        }

        return Str::headline(class_basename($person)) . ' #' . $person->getKey();
    }
}
