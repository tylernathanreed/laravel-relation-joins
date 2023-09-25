<?php

namespace Reedware\LaravelRelationJoins\Mixins;

use Closure;
use Illuminate\Database\Query\Builder;

/** @mixin Builder */
class MergeJoins
{
    /**
     * Defines the mixin for {@see $query->mergeJoins()}.
     */
    public function mergeJoins(): Closure
    {
        /**
         * Merges an array of join clauses and bindings.
         *
         * @param  array  $joins
         * @param  array  $bindings
         * @return void
         */
        return function ($joins, $bindings) {
            /** @var Builder $this */

            $this->joins = array_merge($this->joins ?: [], (array) $joins);

            $this->bindings['join'] = array_values(
                array_merge($this->bindings['join'], (array) $bindings)
            );
        };
    }
}
