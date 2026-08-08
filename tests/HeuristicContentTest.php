<?php

use Blemli\Peek\Content\HeuristicContentSource;
use Blemli\Peek\Content\PeekContent;
use Blemli\Peek\Content\PeekData;
use Blemli\Peek\Tests\Fixtures\Models\Author;

function heuristics(Author $author): PeekData
{
    $peek = PeekData::make();
    app(HeuristicContentSource::class)->apply($peek, $author);

    return $peek;
}

it('picks title, secondary and description by attribute name', function () {
    $peek = heuristics(new Author([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'bio' => str_repeat('Writes about cheese. ', 20),
    ]));

    expect($peek->title)->toBe('Jane Doe')
        ->and($peek->secondary)->toBe('jane@example.com')
        ->and(mb_strlen($peek->description))->toBeLessThanOrEqual(123); // 120 + '...'
});

it('aggregates address parts into one field', function () {
    $peek = heuristics(new Author([
        'name' => 'Jane',
        'street' => 'Bahnhofstrasse 1',
        'zip' => '8001',
        'city' => 'Zürich',
    ]));

    $address = collect($peek->fields)->firstWhere('type', 'address');

    expect($address?->value)->toBe('Bahnhofstrasse 1, 8001 Zürich');
});

it('detects avatars, colors, links and money', function () {
    $peek = heuristics(new Author([
        'name' => 'Jane',
        'avatar' => 'https://example.com/jane.jpg',
        'color' => '00ff00',
        'website' => 'example.com',
        'price' => '1200.5',
        'currency' => 'chf',
    ]));

    expect($peek->image)->toBe('https://example.com/jane.jpg')
        ->and($peek->imageShape)->toBe('avatar')
        ->and(collect($peek->chips)->firstWhere('type', 'color')['value'])->toBe('#00ff00')
        ->and(collect($peek->fields)->firstWhere('type', 'link')?->value)->toBe('example.com')
        ->and(collect($peek->fields)->firstWhere('type', 'money')?->value)->toBe("CHF 1'200.50");
});

it('falls back to a generic title', function () {
    $author = Author::create(['name' => 'x']);
    $author->name = '';

    expect(heuristics($author)->title)->toBe('Author #' . $author->id);
});

it('renders a full card through PeekContent', function () {
    $this->actingAsUser();

    $author = Author::create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'city' => 'Zürich',
    ]);

    $html = app(PeekContent::class)->resolve($author);

    expect($html)->toContain('Jane Doe')
        ->toContain('jane@example.com')
        ->toContain('fi-peek-card');
});

it('prefers a registered custom view')->todo('covered in browser QA with a real panel');
