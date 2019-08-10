<?php

namespace Reedware\LaravelRelationJoins\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait JoinsMorphOneOrManyRelations
{
    use JoinsHasOneOrManyRelations {
        getRelationJoinQuery as getMorphOneOrManyParentRelationJoinQuery;
    }

    /**
     * Adds the constraints for a relationship join.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Builder  $parentQuery
     * @param  string  $type
     * @param  string|null  $alias
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationJoinQuery(Builder $query, Builder $parentQuery, $type = 'inner', $alias = null)
    {
        return $this->getMorphOneOrManyParentRelationJoinQuery($query, $parentQuery, $type, $alias)->where(
            $this->morphType, '=', $this->morphClass
        );
    }

    /**
     * Add the constraints for a relationship query on the same table.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Builder  $parentQuery
     * @param  string  $type
     * @param  string|null  $alias
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationJoinQueryForSelfRelation(Builder $query, Builder $parentQuery, $type = 'inner', $alias = null)
    {
        return $this->getMorphOneOrManyParentRelationJoinQueryForSelfRelation($query, $parentQuery, $type, $alias)->where(
            $this->morphType, '=', $this->morphClass
        );
    }
}