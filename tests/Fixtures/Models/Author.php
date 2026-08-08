<?php

namespace Blemli\Peek\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Deliberately has NO policy: Filament's default (allow) applies.
class Author extends Model
{
    protected $table = 'authors';

    protected $guarded = [];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
