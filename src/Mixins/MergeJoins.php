<?php

namespace Reedware\LaravelRelationJoins\Mixins;

class MergeJoins
{
    /**
     * Defines the mixin for {@see $query->mergeJoins()}.
     *
     * @return \Closure
     */
    public function mergeJoins()
    {
        /**
         * Merges an array of join clauses and bindings.
         *
         * @param  array  $joins
         * @param  array  $bindings
         *
         * @return void
         */
        return function ($joins, $bindings) {
            $this->joins = array_merge($this->joins ?: [], (array) $joins);

            $this->bindings['join'] = array_values(
                array_merge($this->bindings['join'], (array) $bindings)
            );
        };
    }
}
