<?php

use Blemli\Peek\Livewire\PeekBox;
use Blemli\Peek\Support\PeekToken;
use Blemli\Peek\Tests\Fixtures\Models\Author;
use Blemli\Peek\Tests\Fixtures\Models\Post;
use Blemli\Peek\Tests\Fixtures\Models\Secret;
use Blemli\Peek\Tests\Fixtures\Policies\PostPolicy;
use Blemli\Peek\Tests\Fixtures\Resources\PostResource;

use function Pest\Livewire\livewire;

beforeEach(fn () => PeekToken::flushCache());

it('renders the peek card for an authorized token target', function () {
    $this->actingAsUser();
    $author = Author::create(['name' => 'Jane Doe']);

    livewire(PeekBox::class)
        ->dispatch('peek.open', target: 'token:' . PeekToken::make($author), level: 0, key: 'abc123')
        ->assertNotDispatched('peek-denied')
        ->assertSet('stack', function (array $stack): bool {
            return str_contains($stack[0]['html'], 'Jane Doe') && $stack[0]['key'] === 'abc123';
        });
});

it('resolves url targets through the record url resolver', function () {
    $this->actingAsUser();
    $post = Post::create(['title' => 'Hello World']);

    livewire(PeekBox::class)
        ->dispatch('peek.open', target: 'url:' . PostResource::getUrl('view', ['record' => $post]), level: 0, key: 'k')
        ->assertSet('stack', fn (array $stack): bool => str_contains($stack[0]['html'], 'Hello World'));
});

it('silently denies guests', function () {
    $author = Author::create(['name' => 'Jane']);

    livewire(PeekBox::class)
        ->dispatch('peek.open', target: 'token:' . PeekToken::make($author), level: 0, key: 'k')
        ->assertDispatched('peek-denied')
        ->assertSet('stack', []);
});

it('silently denies forbidden records', function () {
    $this->actingAsUser();
    PostPolicy::$allowView = false;
    $post = Post::create(['title' => 'Hello']);

    livewire(PeekBox::class)
        ->dispatch('peek.open', target: 'token:' . PeekToken::make($post), level: 0, key: 'k')
        ->assertDispatched('peek-denied');
});

it('silently denies NoPeeking models', function () {
    $this->actingAsUser();
    $secret = Secret::create(['name' => 'Nuclear codes']);

    livewire(PeekBox::class)
        ->dispatch('peek.open', target: 'token:' . PeekToken::make($secret), level: 0, key: 'k')
        ->assertDispatched('peek-denied');
});

it('clamps the level to the configured depth', function () {
    $this->actingAsUser();
    $author = Author::create(['name' => 'Jane']);

    livewire(PeekBox::class)
        ->dispatch('peek.open', target: 'token:' . PeekToken::make($author), level: 7, key: 'k')
        ->assertSet('stack', fn (array $stack): bool => array_keys($stack) === [0]);
});

it('truncates the stack on close', function () {
    $this->actingAsUser();
    $author = Author::create(['name' => 'Jane']);

    livewire(PeekBox::class)
        ->dispatch('peek.open', target: 'token:' . PeekToken::make($author), level: 0, key: 'k')
        ->dispatch('peek.close', level: 0)
        ->assertSet('stack', []);
});

it('rejects malformed targets without leaking errors', function () {
    $this->actingAsUser();

    livewire(PeekBox::class)
        ->dispatch('peek.open', target: 'nonsense', level: 0, key: 'k')
        ->assertDispatched('peek-denied');
});
