<div
    x-data="peekBox"
    data-peek-root
    data-peek-config="{{ json_encode($config) }}"
>
    @for ($level = 0; $level < $depth; $level++)
        @php
            $slot = $stack[$level] ?? null;
            $serverKey = $slot['key'] ?? '';
        @endphp

        <div
            x-cloak
            x-show="isOpen({{ $level }})"
            x-transition:enter="fi-peek-enter"
            :style="styleFor({{ $level }})"
            data-peek-panel
            data-peek-level="{{ $level }}"
            class="fi-dropdown-panel fi-peek-panel"
            role="dialog"
            aria-label="{{ __('peek::peek.popover.aria_label') }}"
        >
            @if (filled($slot['html'] ?? null))
                <div x-show="hasContent({{ $level }}, '{{ $serverKey }}')">{!! $slot['html'] !!}</div>
                <div x-show="! hasContent({{ $level }}, '{{ $serverKey }}')">@include('peek::skeleton')</div>
            @else
                @include('peek::skeleton')
            @endif
        </div>
    @endfor
</div>
