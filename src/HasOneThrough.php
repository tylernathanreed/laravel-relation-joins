<?php

namespace Reedware\LaravelRelationJoins;

use Illuminate\Database\Eloquent\Relations\HasOneThrough as Relation;

class HasOneThrough extends Relation
{
    use Concerns\ForwardsHasOneThroughCalls;
}