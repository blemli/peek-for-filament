<?php

use Blemli\Peek\Attachment\AttachesPeekToColumns;
use Blemli\Peek\Macros\PeekMacros;
use Blemli\Peek\Support\PeekToken;
use Blemli\Peek\Tests\Fixtures\Models\Author;
use Blemli\Peek\Tests\Fixtures\Models\Post;
use Blemli\Peek\Tests\Fixtures\Models\Secret;
use Filament\Tables\Columns\TextColumn;

beforeEach(function () {
    PeekToken::flushCache();
    AttachesPeekToColumns::register();
    PeekMacros::register();
});

it('attaches a peek token to relationship columns', function () {
    $author = Author::create(['name' => 'Jane']);
    $post = Post::create(['title' => 'Hello', 'author_id' => $author->id]);

    $attributes = TextColumn::make('author.name')->record($post)->getExtraCellAttributes();

    expect($attributes)->toHaveKey('data-peek')
        ->and(PeekToken::parse($attributes['data-peek']))->toBe([Author::class, $author->id]);
});

it('does not attach to plain columns', function () {
    $post = Post::create(['title' => 'Hello']);

    expect(TextColumn::make('title')->record($post)->getExtraCellAttributes())
        ->not->toHaveKey('data-peek');
});

it('does not attach to multiple-record relationships', function () {
    $author = Author::create(['name' => 'Jane']);
    Post::create(['title' => 'Hello', 'author_id' => $author->id]);

    expect(TextColumn::make('posts.title')->record($author)->getExtraCellAttributes())
        ->not->toHaveKey('data-peek');
});

it('marks noPeeking columns with an opt-out attribute', function () {
    $author = Author::create(['name' => 'Jane']);
    $post = Post::create(['title' => 'Hello', 'author_id' => $author->id]);

    $attributes = TextColumn::make('author.name')->noPeeking()->record($post)->getExtraCellAttributes();

    expect($attributes)->toHaveKey('data-peek-off');
});

it('force-enables peeking on plain columns via peekable()', function () {
    $author = Author::create(['name' => 'Jane']);
    $post = Post::create(['title' => 'Hello']);

    $attributes = TextColumn::make('title')
        ->peekable(fn () => $author)
        ->record($post)
        ->getExtraCellAttributes();

    expect(PeekToken::parse($attributes['data-peek'] ?? ''))->toBe([Author::class, $author->id]);
});

it('never attaches NoPeeking models, even via peekable()', function () {
    $secret = Secret::create(['name' => 'Nuclear codes']);
    $post = Post::create(['title' => 'Hello']);

    $attributes = TextColumn::make('title')
        ->peekable(fn () => $secret)
        ->record($post)
        ->getExtraCellAttributes();

    expect($attributes)->not->toHaveKey('data-peek');
});
