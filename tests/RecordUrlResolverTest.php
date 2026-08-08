<?php

use Blemli\Peek\Routing\RecordUrlResolver;
use Blemli\Peek\Tests\Fixtures\Models\Post;
use Blemli\Peek\Tests\Fixtures\Resources\PostResource;

function resolver(): RecordUrlResolver
{
    return app(RecordUrlResolver::class);
}

it('resolves a view page URL to its record', function () {
    $post = Post::create(['title' => 'Hello']);
    $url = PostResource::getUrl('view', ['record' => $post]);

    $resolved = resolver()->resolve($url);

    expect($resolved)->not->toBeNull()
        ->and($resolved->record->is($post))->toBeTrue()
        ->and($resolved->resource)->toBe(PostResource::class);
});

it('resolves an edit page URL to its record', function () {
    $post = Post::create(['title' => 'Hello']);

    expect(resolver()->resolve(PostResource::getUrl('edit', ['record' => $post])))->not->toBeNull();
});

it('ignores query strings and fragments', function () {
    $post = Post::create(['title' => 'Hello']);
    $url = PostResource::getUrl('view', ['record' => $post]) . '?foo=1#bar';

    expect(resolver()->resolve($url)?->record->is($post))->toBeTrue();
});

it('does not resolve index or non-record pages', function () {
    expect(resolver()->resolve(PostResource::getUrl('index')))->toBeNull()
        ->and(resolver()->resolve(url('/admin/login')))->toBeNull();
});

it('does not resolve foreign hosts', function () {
    $post = Post::create(['title' => 'Hello']);

    expect(resolver()->resolve('https://evil.test/admin/posts/' . $post->id))->toBeNull();
});

it('does not resolve dangerous or oversized URLs', function () {
    expect(resolver()->resolve('javascript:alert(1)'))->toBeNull()
        ->and(resolver()->resolve('/admin/posts/' . str_repeat('1', 3000)))->toBeNull();
});

it('does not resolve paths outside the panel', function () {
    $post = Post::create(['title' => 'Hello']);

    expect(resolver()->resolve('/other/posts/' . $post->id))->toBeNull();
});

it('does not resolve missing records', function () {
    expect(resolver()->resolve('/admin/posts/999'))->toBeNull();
});
