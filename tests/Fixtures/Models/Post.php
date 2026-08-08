<?php

namespace Blemli\Peek\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Has a policy with view() but no peek().
class Post extends Model
{
    protected $table = 'posts';

    protected $guarded = [];

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }
}
