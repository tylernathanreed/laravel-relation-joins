<?php

namespace Tests\Models;

class EloquentTaggableModelStub extends EloquentRelationJoinPivotStub
{
    protected $table = 'taggables';

    public function scopeGlobal($query)
    {
        $query->where('scope', '=', 'global');
    }
}
