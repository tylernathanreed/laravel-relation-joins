<?php

namespace Reedware\LaravelRelationJoins\Tests\Models;

class EloquentUserHistoryModelStub extends EloquentRelationJoinModelStub
{
    protected $table = 'history';

    public function user()
    {
        return $this->belongsTo(EloquentUserModelStub::class, 'user_id', 'id');
    }
}
