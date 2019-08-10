<?php

namespace Reedware\LaravelRelationJoins;

use Illuminate\Database\Query\Builder as Query;

class QueryBuilder extends Query
{
    use Concerns\ForwardsParentCalls;
    use Concerns\MergeJoins;

    /**
     * Creates a new instance of this class using a parent instance.
     *
     * @param  mixed  $parent
     * @return static
     */
    public static function newFromParent($parent)
    {
        return new static(
        	$parent->getConnection(),
        	$parent->getGrammar(),
        	$parent->getProcessor()
        );
    }
}