@php
    use Blemli\Peek\Support\PeekToken;
@endphp

{{-- Adaptive slot card: every zone renders only when it has data. --}}
<div
    class="fi-peek-card"
    x-data="{ shape: @js($peek->image ? $peek->imageShape : 'none') }"
>
    @if ($peek->image)
        <div class="fi-peek-banner" x-show="shape === 'banner' || shape === 'auto'">
            <img
                src="{{ $peek->image }}"
                alt=""
                @if ($peek->imageShape === 'auto')
                    x-on:load="shape = ($event.target.naturalWidth >= $event.target.naturalHeight * 1.5) ? 'banner' : 'avatar'"
                @endif
            />
        </div>
    @endif

    <div class="fi-peek-body">
        <div class="fi-peek-header">
            @if ($peek->image)
                <img class="fi-peek-avatar" src="{{ $peek->image }}" alt="" x-show="shape === 'avatar'" x-cloak />
            @endif

            <div class="fi-peek-heading">
                <div class="fi-peek-title-row">
                    @if ($peek->title)
                        <span class="fi-peek-title">{{ $peek->title }}</span>
                    @endif

                    @if ($peek->kicker)
                        <span class="fi-peek-kicker">{{ $peek->kicker }}</span>
                    @endif
                </div>

                @if ($peek->secondary)
                    <div class="fi-peek-secondary">{{ $peek->secondary }}</div>
                @endif
            </div>
        </div>

        @if ($peek->description)
            <p class="fi-peek-description">{{ $peek->description }}</p>
        @endif

        @if ($peek->fields !== [])
            <dl class="fi-peek-meta">
                @foreach ($peek->fields as $field)
                    <dt>{{ $field->label }}</dt>
                    <dd>@include('peek::content.fields.' . $field->type, ['field' => $field])</dd>
                @endforeach
            </dl>
        @endif

        @if ($peek->person)
            @php
                $person = $peek->person['record'];
                $personTitle = $peek->person['title'] ?? ('#' . $person->getKey());
            @endphp

            <div
                class="fi-peek-person"
                @if ($canNest) data-peek="{{ PeekToken::make($person) }}" @endif
            >
                <span class="fi-peek-person-initial" aria-hidden="true">{{ mb_substr($personTitle, 0, 1) }}</span>
                <span class="fi-peek-person-name">{{ $personTitle }}</span>
            </div>
        @endif

        @if ($peek->chips !== [] || $peek->url)
            <div class="fi-peek-footer">
                @foreach ($peek->chips as $chip)
                    @if ($chip['type'] === 'color')
                        <span class="fi-peek-chip">
                            <span class="fi-peek-swatch" style="background-color: {{ $chip['value'] }}"></span>
                            {{ $chip['label'] ?? $chip['value'] }}
                        </span>
                    @elseif ($chip['type'] === 'icon' && str_starts_with($chip['value'], 'heroicon-'))
                        <span class="fi-peek-chip">
                            <x-filament::icon :icon="$chip['value']" class="fi-peek-chip-icon" />
                            @if ($chip['label']) {{ $chip['label'] }} @endif
                        </span>
                    @elseif ($chip['type'] === 'badge')
                        <x-filament::badge size="sm">{{ $chip['value'] }}</x-filament::badge>
                    @endif
                @endforeach

                @if ($peek->url)
                    <a href="{{ $peek->url }}" data-peek-off class="fi-peek-open">
                        {{ __('peek::peek.popover.open') }}
                        <x-filament::icon icon="heroicon-m-arrow-up-right" class="fi-peek-open-icon" />
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>
