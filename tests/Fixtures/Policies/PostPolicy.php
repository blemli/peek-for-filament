<?php

namespace Blemli\Peek\Tests\Fixtures\Policies;

use Blemli\Peek\Tests\Fixtures\Models\Post;
use Blemli\Peek\Tests\Fixtures\Models\User;

class PostPolicy
{
    public static bool $allowView = true;

    public function view(User $user, Post $post): bool
    {
        return static::$allowView;
    }
}
