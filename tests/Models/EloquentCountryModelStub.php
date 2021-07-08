<?php

namespace Reedware\LaravelRelationJoins\Tests\Models;

class EloquentCountryModelStub extends EloquentRelationJoinModelStub
{
    protected $table = 'countries';

    public function users()
    {
        return $this->hasMany(EloquentUserModelStub::class, 'country_id', 'id');
    }

    public function posts()
    {
        return $this->hasManyThrough(EloquentPostModelStub::class, EloquentUserModelStub::class, 'country_id', 'user_id', 'id', 'id');
    }

    public function postsThroughSoftDeletingUser()
    {
        return $this->hasManyThrough(EloquentPostModelStub::class, EloquentSoftDeletingUserModelStub::class, 'country_id', 'user_id', 'id', 'id');
    }

    public function softDeletingPosts()
    {
        return $this->hasManyThrough(EloquentSoftDeletingPostModelStub::class, EloquentUserModelStub::class, 'country_id', 'user_id', 'id', 'id');
    }
}
