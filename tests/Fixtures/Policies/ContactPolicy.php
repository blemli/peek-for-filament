<?php

namespace Blemli\Peek\Tests\Fixtures\Policies;

use Blemli\Peek\Tests\Fixtures\Models\Contact;
use Blemli\Peek\Tests\Fixtures\Models\User;

class ContactPolicy
{
    public static bool $allowPeek = true;

    public static bool $allowView = true;

    public function peek(User $user, Contact $contact): bool
    {
        return static::$allowPeek;
    }

    public function view(User $user, Contact $contact): bool
    {
        return static::$allowView;
    }
}
