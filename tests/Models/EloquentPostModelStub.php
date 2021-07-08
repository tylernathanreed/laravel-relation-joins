<?php

namespace Reedware\LaravelRelationJoins\Tests\Models;

class EloquentPostModelStub extends EloquentRelationJoinModelStub
{
    protected $table = 'posts';

    public function comments()
    {
        return $this->hasMany(EloquentCommentModelStub::class, 'post_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(EloquentUserModelStub::class, 'user_id', 'id');
    }

    public function image()
    {
        return $this->morphOne(EloquentImageModelStub::class, 'imageable');
    }

    public function postImage()
    {
        return $this->hasOne(EloquentImageModelStub::class, 'imageable_id')->where('imageable_type', '=', static::class);
    }

    public function polymorphicComments()
    {
        return $this->morphMany(EloquentPolymorphicCommentModelStub::class, 'commentable');
    }

    public function tags()
    {
        return $this->morphToMany(EloquentTagModelStub::class, 'taggable', 'taggables', 'taggable_id', 'tag_id', 'id');
    }

    public function tagsUsingPivotModel()
    {
        return $this->morphToMany(EloquentTagModelStub::class, 'taggable', 'taggables', 'taggable_id', 'tag_id', 'id')
            ->using(EloquentTaggableModelStub::class);
    }

    public function tagsUsingSoftDeletingPivotModel()
    {
        return $this->morphToMany(EloquentTagModelStub::class, 'taggable', 'taggables', 'taggable_id', 'tag_id', 'id')
            ->using(EloquentSoftDeletingTaggableModelStub::class);
    }

    public function likes()
    {
        return $this->hasManyThrough(EloquentLikeModelStub::class, EloquentCommentModelStub::class, 'post_id', 'comment_id', 'id', 'id');
    }
}
