<?php

namespace Reedware\LaravelRelationJoins\Mixins;

use Illuminate\Database\Eloquent\Builder;
use Reedware\LaravelRelationJoins\RelationJoinQuery;

class RelationJoinQueries
{
    /**
     * Defines the mixin for {@see $relation->getRelationJoinQuery()}.
     *
     * @return \Closure
     */
    public function getRelationJoinQuery()
    {
        /**
         * Adds the constraints for a relationship join.
         *
         * @param  \Illuminate\Database\Eloquent\Builder  $query
         * @param  \Illuminate\Database\Eloquent\Builder  $parentQuery
         * @param  string                                 $type
         * @param  string|null                            $alias
         *
         * @return \Illuminate\Database\Eloquent\Builder
         */
        return function (Builder $query, Builder $parentQuery, $type = 'inner', $alias = null) {
            return RelationJoinQuery::get($this, $query, $parentQuery, $type, $alias);
        };
    }
}
