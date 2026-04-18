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
         */
        return function (
            Closure|string $first,
            ?string $operator = null,
            ?string $second = null,
            string $boolean = 'and'
        ): Builder {
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
         */
        return function (Closure|string $first, ?string $operator = null, ?string $second = null): Builder {
            /** @var Builder $this */
            return $this->on($first, $operator, $second, 'or');
        };
    }
}
