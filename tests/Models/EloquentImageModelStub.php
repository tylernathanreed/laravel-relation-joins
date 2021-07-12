<?php

namespace Reedware\LaravelRelationJoins\Tests\Models;

class EloquentImageModelStub extends EloquentRelationJoinModelStub
{
    protected $table = 'images';

    public function imageable()
    {
        return $this->morphTo('imageable');
    }

    public function postImageable()
    {
        return $this->belongsTo(EloquentPostModelStub::class, 'imageable_id')->where('imageable_type', '=', EloquentPostModelStub::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(EloquentUserModelStub::class, 'uploaded_by_id');
    }
}
