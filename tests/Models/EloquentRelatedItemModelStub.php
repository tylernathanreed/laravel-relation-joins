<?php

namespace Reedware\LaravelRelationJoins\Tests\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;

class EloquentRelatedItemModelStub extends EloquentRelationJoinModelStub
{
    public function related(): MorphTo
    {
        return $this->morphTo('related');
    }

    public function getTable(): string
    {
        return $this->table ?? 'get_table_override';
    }
}
