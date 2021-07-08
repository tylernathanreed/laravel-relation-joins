<?php

namespace Reedware\LaravelRelationJoins\Tests\Models;

class EloquentPhoneModelStub extends EloquentRelationJoinModelStub
{
    protected $table = 'phones';

    public function user()
    {
        return $this->belongsTo(EloquentUserModelStub::class, 'user_id', 'id');
    }

    public function softDeletingUser()
    {
        return $this->belongsTo(EloquentSoftDeletingUserModelStub::class, 'user_id', 'id');
    }
}
