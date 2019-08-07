<?php

namespace Reedware\LaravelRelationJoins\Relations;

use Illuminate\Database\Eloquent\Builder;

abstract class MorphOneOrMany extends HasOneOrMany
{
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
        return parent::getRelationJoinQuery($query, $parentQuery, $type, $alias)->where(
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
        return parent::getRelationJoinQueryForSelfRelation($query, $parentQuery, $type, $alias)->where(
            $this->morphType, '=', $this->morphClass
        );
    }
}