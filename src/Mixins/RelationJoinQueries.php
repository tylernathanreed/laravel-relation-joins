<?php

namespace Reedware\LaravelRelationJoins\Mixins;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Reedware\LaravelRelationJoins\RelationJoinQuery;

/** @mixin Relation */
class RelationJoinQueries
{
    /**
     * Defines the mixin for {@see $relation->getRelationJoinQuery()}.
     */
    public function getRelationJoinQuery(): Closure
    {
        /**
         * Adds the constraints for a relationship join.
         */
        return function (Builder $query, Builder $parentQuery, string $type = 'inner', ?string $alias = null): Builder {
            /** @var Relation $this */
            return RelationJoinQuery::get($this, $query, $parentQuery, $type, $alias);
        };
    }
}
