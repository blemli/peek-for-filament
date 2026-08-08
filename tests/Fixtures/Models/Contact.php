<?php

namespace Blemli\Peek\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

// Has a policy with BOTH peek() and view().
class Contact extends Model
{
    protected $table = 'contacts';

    protected $guarded = [];
}
