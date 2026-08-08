<?php

namespace Blemli\Peek\Livewire;

use Blemli\Peek\Content\PeekContent;
use Blemli\Peek\Peek;
use Blemli\Peek\PeekPlugin;
use Filament\Facades\Filament;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Singleton popover host, injected once per page via render hook.
 * Holds one slot per nesting level; content is server-rendered HTML.
 */
class PeekBox extends Component
{
    /**
     * @var array<int, array{key: string, html: string}>
     */
    public array $stack = [];

    #[On('peek.open')]
    public function open(string $target, int $level = 0, string $key = ''): void
    {
        $level = max(0, min($level, $this->getDepth() - 1));
        $key = substr((string) preg_replace('/[^a-z0-9]/i', '', $key), 0, 32);

        $this->truncate($level);

        $peek = app(Peek::class);
        $record = $peek->resolveAuthorized($target);
        $html = $record ? app(PeekContent::class)->resolve($record, $level) : null;

        if (blank($html)) {
            // A denial is indistinguishable from "nothing to show".
            $this->dispatch('peek-denied', level: $level);

            return;
        }

        $peek->logPeek($record);

        $this->stack[$level] = ['key' => $key, 'html' => $html];
    }

    #[On('peek.close')]
    public function close(int $level = 0): void
    {
        $this->truncate($level);
    }

    protected function truncate(int $level): void
    {
        $this->stack = array_filter(
            $this->stack,
            fn (int $stackLevel): bool => $stackLevel < $level,
            ARRAY_FILTER_USE_KEY,
        );
    }

    protected function getDepth(): int
    {
        return PeekPlugin::tryGet()?->getPeekDepth() ?? max(1, (int) config('peek.depth', 1));
    }

    public function render(): View
    {
        $plugin = PeekPlugin::tryGet();

        return view('peek::peek-box', [
            'depth' => $this->getDepth(),
            'config' => [
                'hoverDelay' => $plugin?->getHoverDelay() ?? (int) config('peek.hover_delay', 400),
                'depth' => $this->getDepth(),
                'anchors' => $plugin?->shouldPeekAnchors() ?? (bool) config('peek.anchors', true),
                'ignoreSelectors' => $plugin?->getIgnoredAnchorSelectors() ?? [],
                'longPress' => $plugin?->isMobileLongPressEnabled() ?? (bool) config('peek.mobile.enabled', true),
                'longPressDuration' => $plugin?->getLongPressDuration() ?? (int) config('peek.mobile.long_press_duration', 500),
                'panelUrl' => Filament::getCurrentOrDefaultPanel()?->getUrl() ?? url('/'),
            ],
        ]);
    }
}
