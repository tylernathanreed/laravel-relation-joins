<?php

namespace Reedware\LaravelRelationJoins\Tests\Models;

class EloquentTagModelStub extends EloquentRelationJoinModelStub
{
    protected $table = 'tags';

    public function posts()
    {
        return $this->morphedByMany(EloquentPostModelStub::class, 'taggable', 'taggables', 'tag_id');
    }

    public function tags()
    {
        return $this->morphedByMany(static::class, 'taggable', 'taggables', 'tag_id');
    }

    public function videos()
    {
        return $this->morphedByMany(EloquentVideoModelStub::class, 'taggable');
    }
}
