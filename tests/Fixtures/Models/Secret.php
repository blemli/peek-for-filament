<?php

namespace Blemli\Peek\Tests\Fixtures\Models;

use Blemli\Peek\Contracts\NoPeeking;
use Illuminate\Database\Eloquent\Model;

class Secret extends Model implements NoPeeking
{
    protected $table = 'secrets';

    protected $guarded = [];
}
