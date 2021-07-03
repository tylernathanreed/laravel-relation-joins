<?php

namespace Reedware\LaravelRelationJoins\Tests\Models;

class EloquentVideoModelStub extends EloquentRelationJoinModelStub
{
    protected $table = 'videos';

    public function polymorphicComments()
    {
        return $this->morphMany(EloquentPolymorphicCommentModelStub::class, 'commentable');
    }

    public function videoComments()
    {
        return $this->hasMany(EloquentPolymorphicCommentModelStub::class, 'commentable_id')->where('commentable_type', '=', static::class);
    }

    public function tags()
    {
        return $this->morphToMany(EloquentTagModelStub::class, 'taggable');
    }

    public function videoTags()
    {
        return $this->belongsToMany(EloquentTagModelStub::class, 'taggables', 'taggable_id', 'tag_id')->wherePivot('taggable_type', '=', static::class);
    }
}
