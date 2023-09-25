<?php

namespace Reedware\LaravelRelationJoins\Mixins;

use Closure;
use Illuminate\Database\Query\Builder;

/** @mixin Builder */
class JoinOperations
{
    /**
     * Defines the mixin for {@see $query->on()}.
     */
    public function on(): Closure
    {
        /**
         * Add an "on" clause to the join.
         *
         * @param  \Closure|string  $first
         * @param  string|null  $operator
         * @param  string|null  $second
         * @param  string  $boolean
         * @return $this
         */
        return function ($first, $operator = null, $second = null, $boolean = 'and') {
            /** @var Builder $this */

            if ($first instanceof Closure) {
                return $this->whereNested($first, $boolean);
            }

            return $this->whereColumn($first, $operator, $second, $boolean);
        };
    }

    /**
     * Defines the mixin for {@see $query->orOn()}.
     */
    public function orOn(): Closure
    {
        /**
         * Add an "or on" clause to the join.
         *
         * @param  \Closure|string  $first
         * @param  string|null  $operator
         * @param  string|null  $second
         * @return $this
         */
        return function ($first, $operator = null, $second = null) {
            /** @var Builder $this */
            return $this->on($first, $operator, $second, 'or');
        };
    }
}
