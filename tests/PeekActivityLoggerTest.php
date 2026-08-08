<?php

use Blemli\Peek\Activity\PeekActivityLogger;
use Blemli\Peek\Peek;
use Blemli\Peek\Tests\Fixtures\Models\Post;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    config(['peek.activity_log.enabled' => true, 'cache.default' => 'array']);
});

afterEach(fn () => PeekActivityLogger::$availabilityCheck = null);

it('logs a peek with log name, causer, subject and event', function () {
    $user = $this->actingAsUser();
    $post = Post::create(['title' => 'Hello']);

    app(PeekActivityLogger::class)->log($post);

    $activity = Activity::sole();

    expect($activity->log_name)->toBe('peek')
        ->and($activity->event)->toBe('peeked')
        ->and($activity->description)->toBe('peeked')
        ->and($activity->causer->is($user))->toBeTrue()
        ->and($activity->subject->is($post))->toBeTrue();
});

it('throttles repeat peeks within the window', function () {
    $this->actingAsUser();
    $post = Post::create(['title' => 'Hello']);
    $logger = app(PeekActivityLogger::class);

    $logger->log($post);
    $logger->log($post);
    expect(Activity::count())->toBe(1);

    $this->travel(11)->minutes();
    $logger->log($post);
    expect(Activity::count())->toBe(2);
});

it('logs different records separately', function () {
    $this->actingAsUser();
    $logger = app(PeekActivityLogger::class);

    $logger->log(Post::create(['title' => 'One']));
    $logger->log(Post::create(['title' => 'Two']));

    expect(Activity::count())->toBe(2);
});

it('does nothing when logging is disabled', function () {
    config(['peek.activity_log.enabled' => false]);
    $this->actingAsUser();
    $post = Post::create(['title' => 'Hello']);

    app(Peek::class)->logPeek($post);

    expect(Activity::count())->toBe(0);
});

it('is a silent no-op when spatie/laravel-activitylog is absent', function () {
    $this->actingAsUser();
    $post = Post::create(['title' => 'Hello']);

    PeekActivityLogger::$availabilityCheck = fn (): bool => false;
    app(PeekActivityLogger::class)->log($post);

    expect(Activity::count())->toBe(0);
});
