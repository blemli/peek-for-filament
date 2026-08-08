<?php

namespace Blemli\Peek\Content;

use Illuminate\Database\Eloquent\Model;

final class PeekData
{
    /**
     * @param  array<PeekField>  $fields  label/value pairs for the meta grid
     * @param  array<array{type: string, value: string, label: string|null}>  $chips  badge/color/icon chips
     * @param  array{record: Model, title: string|null}|null  $person
     */
    public function __construct(
        public ?string $title = null,
        public ?string $kicker = null, // model label, shown small next to the title
        public ?string $secondary = null,
        public ?string $description = null,
        public ?string $image = null,
        public string $imageShape = 'auto', // auto | avatar | banner
        public ?string $url = null,
        public array $fields = [],
        public array $chips = [],
        public ?array $person = null,
    ) {}

    public static function make(): self
    {
        return new self;
    }

    public function title(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function kicker(?string $kicker): static
    {
        $this->kicker = $kicker;

        return $this;
    }

    public function secondary(?string $secondary): static
    {
        $this->secondary = $secondary;

        return $this;
    }

    public function description(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function image(?string $image, string $shape = 'auto'): static
    {
        $this->image = $image;
        $this->imageShape = $shape;

        return $this;
    }

    public function url(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function field(PeekField $field): static
    {
        $this->fields[] = $field;

        return $this;
    }

    public function chip(string $type, string $value, ?string $label = null): static
    {
        $this->chips[] = ['type' => $type, 'value' => $value, 'label' => $label];

        return $this;
    }

    public function person(?Model $record, ?string $title = null): static
    {
        if (! $record) {
            return $this;
        }

        $this->person = [
            'record' => $record,
            'title' => $title,
        ];

        return $this;
    }

    public function isEmpty(): bool
    {
        return blank($this->title)
            && blank($this->secondary)
            && blank($this->description)
            && blank($this->image)
            && $this->fields === []
            && $this->chips === []
            && $this->person === null;
    }
}
