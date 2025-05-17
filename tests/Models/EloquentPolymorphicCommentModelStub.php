<?php

namespace Tests\Models;

class EloquentPolymorphicCommentModelStub extends EloquentRelationJoinModelStub
{
    protected $table = 'comments';

    public function commentable()
    {
        return $this->morphTo('commentable');
    }
}
