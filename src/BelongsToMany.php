<?php

namespace Reedware\LaravelRelationJoins;

use Illuminate\Database\Eloquent\Relations\BelongsToMany as Relation;

class BelongsToMany extends Relation
{
    use Concerns\ForwardsBelongsToManyCalls;
}