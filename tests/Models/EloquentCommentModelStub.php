<?php

namespace Reedware\LaravelRelationJoins\Tests\Models;

class EloquentCommentModelStub extends EloquentRelationJoinModelStub
{
    protected $table = 'comments';

    public function post()
    {
        return $this->belongsTo(EloquentPostModelStub::class, 'post_id', 'id');
    }

    public function likes()
    {
        return $this->hasMany(EloquentLikeModelStub::class, 'comment_id', 'id');
    }
}
