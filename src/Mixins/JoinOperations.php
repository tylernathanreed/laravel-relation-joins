<?php

namespace Reedware\LaravelRelationJoins\Mixins;

use Closure;

class JoinOperations
{
    /**
     * Defines the mixin for {@see $query->on()}.
     *
     * @return \Closure
     */
    public function on()
    {
        /**
         * Add an "on" clause to the join.
         *
         * @param  \Closure|string  $first
         * @param  string|null      $operator
         * @param  string|null      $second
         * @param  string           $boolean
         *
         * @return $this
         */
        return function ($first, $operator = null, $second = null, $boolean = 'and') {

            if ($first instanceof Closure) {
                return $this->whereNested($first, $boolean);
            }

            return $this->whereColumn($first, $operator, $second, $boolean);

        };
    }

    /**
     * Defines the mixin for {@see $query->orOn()}.
     *
     * @return \Closure
     */
    public function orOn()
    {
        /**
         * Add an "or on" clause to the join.
         *
         * @param  \Closure|string  $first
         * @param  string|null      $operator
         * @param  string|null      $second
         *
         * @return $this
         */
        return function ($first, $operator = null, $second = null) {
            return $this->on($first, $operator, $second, 'or');
        };
    }
}
