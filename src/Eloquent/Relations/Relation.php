<?php

namespace Reedware\LaravelRelationJoins\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation as BaseRelation;

abstract class Relation extends BaseRelation
{
    /**
     * Add the constraints for a relationship join query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Builder  $parentQuery
     * @param  string  $type
     * @param  string|null  $alias
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationJoinQuery(Builder $query, Builder $parentQuery, $type = 'inner', $alias = null)
    {
        return $query->whereColumn(
            $this->getExistenceCompareKey(), '=', $this->getQualifiedParentKeyName()
        );
    }
}