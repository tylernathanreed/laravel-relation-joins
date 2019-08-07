<?php

namespace Reedware\LaravelRelationJoins\Query;

use Illuminate\Database\Query\Builder as Query;

class Builder extends Query
{
    /**
     * Merge an array of join clauses and bindings.
     *
     * @param  array  $joins
     * @param  array  $bindings
     * @return void
     */
    public function mergeJoins($joins, $bindings)
    {
        $this->joins = array_merge($this->joins ?: [], (array) $joins);

        $this->bindings['join'] = array_values(
            array_merge($this->bindings['join'], (array) $bindings)
        );
    }
}