<?php

use Blemli\Peek\Authorization\PeekAuthorizer;
use Blemli\Peek\Tests\Fixtures\Models\Author;
use Blemli\Peek\Tests\Fixtures\Models\Contact;
use Blemli\Peek\Tests\Fixtures\Models\Post;
use Blemli\Peek\Tests\Fixtures\Models\Secret;
use Blemli\Peek\Tests\Fixtures\Policies\ContactPolicy;
use Blemli\Peek\Tests\Fixtures\Policies\PostPolicy;
use Illuminate\Support\Facades\Gate;

function authorizer(): PeekAuthorizer
{
    return app(PeekAuthorizer::class);
}

it('denies guests', function () {
    $author = Author::create(['name' => 'Jane']);

    expect(authorizer()->canPeek($author))->toBeFalse();
});

it('allows models without a policy, following the Filament default', function () {
    $this->actingAsUser();
    $author = Author::create(['name' => 'Jane']);

    expect(authorizer()->canPeek($author))->toBeTrue();
});

it('falls back to the view ability when the policy has no peek method', function () {
    $this->actingAsUser();
    $post = Post::create(['title' => 'Hello']);

    PostPolicy::$allowView = true;
    expect(authorizer()->canPeek($post))->toBeTrue();

    PostPolicy::$allowView = false;
    expect(authorizer()->canPeek($post))->toBeFalse();
});

it('lets a dedicated peek ability win over view in both directions', function () {
    $this->actingAsUser();
    $contact = Contact::create(['name' => 'Carla']);

    ContactPolicy::$allowPeek = false;
    ContactPolicy::$allowView = true;
    expect(authorizer()->canPeek($contact))->toBeFalse();

    ContactPolicy::$allowPeek = true;
    ContactPolicy::$allowView = false;
    expect(authorizer()->canPeek($contact))->toBeTrue();
});

it('denies models implementing NoPeeking', function () {
    $this->actingAsUser();
    $secret = Secret::create(['name' => 'Nuclear codes']);

    expect(authorizer()->canPeek($secret))->toBeFalse()
        ->and(authorizer()->isPeekable($secret))->toBeFalse();
});

it('denies models on the global no_peeking list', function () {
    $this->actingAsUser();
    config(['peek.no_peeking.models' => [Author::class]]);
    $author = Author::create(['name' => 'Jane']);

    expect(authorizer()->canPeek($author))->toBeFalse();
});

it('honors Gate::before denials', function () {
    $this->actingAsUser();
    Gate::before(fn () => false);
    $author = Author::create(['name' => 'Jane']);

    expect(authorizer()->canPeek($author))->toBeFalse();
});
