<?php

namespace Reedware\LaravelRelationJoins\Tests\Models;

class EloquentSupplierModelStub extends EloquentRelationJoinModelStub
{
    protected $table = 'suppliers';

    public function userHistory()
    {
        return $this->hasOneThrough(EloquentUserHistoryModelStub::class, EloquentUserModelStub::class, 'supplier_id', 'user_id', 'id', 'id');
    }

    public function userHistoryThroughSoftDeletingUser()
    {
        return $this->hasOneThrough(EloquentUserHistoryModelStub::class, EloquentSoftDeletingUserModelStub::class, 'supplier_id', 'user_id', 'id', 'id');
    }
}
