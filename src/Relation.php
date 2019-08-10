<?php

namespace Reedware\LaravelRelationJoins;

use Illuminate\Database\Eloquent\Relations\Relation as LaravelRelation;

abstract class Relation extends LaravelRelation
{
    use Concerns\ForwardsRelationCalls;
}