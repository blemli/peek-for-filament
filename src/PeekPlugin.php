<?php

namespace Blemli\Peek;

use Blemli\Peek\Attachment\AttachesPeekToColumns;
use Blemli\Peek\Attachment\AttachesPeekToEntries;
use Blemli\Peek\Livewire\PeekBox;
use Blemli\Peek\Macros\PeekMacros;
use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;

class PeekPlugin implements Plugin
{
    protected bool | Closure | null $enabled = null;

    protected ?int $hoverDelay = null;

    protected ?int $depth = null;

    protected ?bool $mobileLongPress = null;

    protected bool | Closure | null $logActivity = null;

    protected ?int $activityLogThrottleMinutes = null;

    protected ?bool $peekAnchors = null;

    protected bool $recently = true;

    protected bool $activitylogTimeline = true;

    /**
     * @var array<class-string>
     */
    protected array $noPeeking = [];

    /**
     * @var array<class-string<Model>, string>
     */
    protected array $peekViews = [];

    /**
     * @var array<string>
     */
    protected array $ignoreAnchorsIn = [];

    public function getId(): string
    {
        return 'peek';
    }

    public function register(Panel $panel): void
    {
        Livewire::component('peek-box', PeekBox::class);

        $panel->renderHook(
            PanelsRenderHook::BODY_END,
            fn (): string => $this->isEnabled() ? Blade::render('@livewire(\'peek-box\')') : '',
        );
    }

    public function boot(Panel $panel): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        AttachesPeekToColumns::register();
        AttachesPeekToEntries::register();
        PeekMacros::register();
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public static function tryGet(): ?static
    {
        try {
            return static::get();
        } catch (\Throwable) {
            return null;
        }
    }

    public function enabled(bool | Closure $condition = true): static
    {
        $this->enabled = $condition;

        return $this;
    }

    public function noPeeking(array | bool $modelsOrResources = true): static
    {
        if (is_bool($modelsOrResources)) {
            $this->enabled = ! $modelsOrResources;

            return $this;
        }

        $this->noPeeking = [...$this->noPeeking, ...$modelsOrResources];

        return $this;
    }

    public function hoverDelay(int $milliseconds): static
    {
        $this->hoverDelay = $milliseconds;

        return $this;
    }

    public function peekDepth(int $depth): static
    {
        $this->depth = max(1, $depth);

        return $this;
    }

    public function mobileLongPress(bool $enabled = true): static
    {
        $this->mobileLongPress = $enabled;

        return $this;
    }

    public function logActivity(bool | Closure $condition = true, ?int $throttleMinutes = null): static
    {
        $this->logActivity = $condition;
        $this->activityLogThrottleMinutes = $throttleMinutes;

        return $this;
    }

    /**
     * Peek on any anchor that links to a resource record.
     */
    public function peekAnchors(bool $enabled = true): static
    {
        $this->peekAnchors = $enabled;

        return $this;
    }

    /**
     * Peek on entries of the awcodes/recently menu.
     */
    public function recently(bool $enabled = true): static
    {
        $this->recently = $enabled;

        return $this;
    }

    /**
     * Peek on record links inside the ralphjsmit/laravel-filament-activitylog timeline.
     */
    public function activitylogTimeline(bool $enabled = true): static
    {
        $this->activitylogTimeline = $enabled;

        return $this;
    }

    /**
     * Never peek on anchors inside elements matching these CSS selectors.
     *
     * @param  array<string>  $cssSelectors
     */
    public function ignoreAnchorsIn(array $cssSelectors): static
    {
        $this->ignoreAnchorsIn = [...$this->ignoreAnchorsIn, ...$cssSelectors];

        return $this;
    }

    /**
     * Use a custom Blade view for a model's peek card.
     *
     * @param  class-string<Model>  $model
     */
    public function peekView(string $model, string $view): static
    {
        $this->peekViews[$model] = $view;

        return $this;
    }

    public function isEnabled(): bool
    {
        $enabled = $this->enabled instanceof Closure
            ? (bool) call_user_func($this->enabled)
            : $this->enabled;

        return $enabled ?? (bool) config('peek.enabled', true);
    }

    public function getHoverDelay(): int
    {
        return $this->hoverDelay ?? (int) config('peek.hover_delay', 400);
    }

    public function getPeekDepth(): int
    {
        return $this->depth ?? max(1, (int) config('peek.depth', 1));
    }

    public function isMobileLongPressEnabled(): bool
    {
        return $this->mobileLongPress ?? (bool) config('peek.mobile.enabled', true);
    }

    public function getLongPressDuration(): int
    {
        return (int) config('peek.mobile.long_press_duration', 500);
    }

    public function shouldLogActivity(): bool
    {
        $logActivity = $this->logActivity instanceof Closure
            ? (bool) call_user_func($this->logActivity)
            : $this->logActivity;

        return $logActivity ?? (bool) config('peek.activity_log.enabled', false);
    }

    public function getActivityLogThrottleMinutes(): int
    {
        return $this->activityLogThrottleMinutes ?? (int) config('peek.activity_log.throttle_minutes', 10);
    }

    public function shouldPeekAnchors(): bool
    {
        return $this->peekAnchors ?? (bool) config('peek.anchors', true);
    }

    /**
     * @return array<class-string>
     */
    public function getNoPeekingList(): array
    {
        return array_unique([
            ...$this->noPeeking,
            ...(array) config('peek.no_peeking.models', []),
            ...(array) config('peek.no_peeking.resources', []),
        ]);
    }

    /**
     * @return array<class-string<Model>, string>
     */
    public function getPeekViews(): array
    {
        return $this->peekViews;
    }

    /**
     * @return array<string>
     */
    public function getIgnoredAnchorSelectors(): array
    {
        return array_values(array_unique(array_filter([
            ...$this->ignoreAnchorsIn,
            $this->recently ? null : '.recently-menu',
            $this->activitylogTimeline ? null : '.fi-rjs-activitylog-timeline',
        ])));
    }
}
