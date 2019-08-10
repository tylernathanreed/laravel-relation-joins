<?php

namespace Reedware\LaravelRelationJoins;

use Illuminate\Database\Eloquent\Relations\HasManyThrough as Relation;

class HasManyThrough extends Relation
{
    use Concerns\ForwardsHasManyThroughCalls;

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
    	//
    }
}