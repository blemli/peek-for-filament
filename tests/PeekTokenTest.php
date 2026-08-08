<?php

use Blemli\Peek\Support\PeekToken;
use Blemli\Peek\Tests\Fixtures\Models\Author;
use Illuminate\Support\Facades\Crypt;

beforeEach(fn () => PeekToken::flushCache());

it('roundtrips a record through make and parse', function () {
    $author = Author::create(['name' => 'Jane Doe']);

    $token = PeekToken::make($author);

    expect(PeekToken::parse($token))->toBe([Author::class, $author->id]);
});

it('rejects tampered tokens', function () {
    expect(PeekToken::parse('garbage'))->toBeNull()
        ->and(PeekToken::parse(base64_encode('{"m":"x","k":1}')))->toBeNull();
});

it('rejects tokens for non-model classes', function () {
    $token = Crypt::encryptString(json_encode(['m' => 'stdClass', 'k' => 1]));

    expect(PeekToken::parse($token))->toBeNull();
});

it('rejects tokens without a key', function () {
    $token = Crypt::encryptString(json_encode(['m' => Author::class]));

    expect(PeekToken::parse($token))->toBeNull();
});
