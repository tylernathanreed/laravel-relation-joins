<?php

namespace Reedware\LaravelRelationJoins\Tests\Models;

class EloquentFileModelStub extends EloquentRelationJoinModelStub
{
    protected $table = 'files';

    public function link()
    {
        return $this->morphTo('link');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(EloquentUserModelStub::class, 'uploaded_by_id');
    }
}
