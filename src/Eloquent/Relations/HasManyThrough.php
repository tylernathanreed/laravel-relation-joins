<?php

namespace Reedware\LaravelRelationJoins\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasManyThrough as Relation;

class HasManyThrough extends Relation
{
    /**
     * Add the constraints for a relationship query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Builder  $parentQuery
     * @param  string  $type
     * @param  string|null  $alias
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationJoinQuery(Builder $query, Builder $parentQuery, $type = 'inner', $alias = null)
    {
        if (strpos($alias, ',') !== false) {
            [$throughAlias, $farAlias] = explode(',', $alias);
        } else {
            [$throughAlias, $farAlias] = [null, $alias];
        }

        if (is_null($farAlias) && $parentQuery->getQuery()->from === $query->getQuery()->from) {
            $farAlias = $this->getRelationCountHash();
        }

        if (is_null($throughAlias) && $parentQuery->getQuery()->from === $this->throughParent->getTable()) {
            $throughAlias = $this->getRelationCountHash();
        }

        if (! is_null($farAlias) && $farAlias != $query->getModel()->getTable()) {
            $query->from($query->getModel()->getTable().' as '.$farAlias);

            $query->getModel()->setTable($farAlias);
        }

        if (! is_null($throughAlias) && $throughAlias != $this->throughParent->getTable()) {
            $table = $this->throughParent->getTable().' as '.$throughAlias;

            $on = $throughAlias;
        } else {
            $table = $on = $this->throughParent->getTable();
        }

        $query->join($table, function ($join) use ($parentQuery, $on) {
            $join->on($on.'.'.$this->firstKey, '=', $parentQuery->qualifyColumn($this->localKey));

            if ($this->throughParentSoftDeletes()) {
                $join->whereNull($on.'.'.$this->throughParent->getDeletedAtColumn());
            }
        }, null, null, $type);

        return $query->whereColumn(
            $query->qualifyColumn($this->secondKey), '=', $on.'.'.$this->secondLocalKey
        );
    }

    /**
     * Set the join clause for the relation query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @param  string  $type
     * @return $this
     */
    protected function performRelationJoin($query = null, $type = 'inner')
    {
        $query = $query ?: $this->query;

        $query->join($this->throughParent->getTable(), function ($join) {
            $join->on($this->getQualifiedFirstKeyName(), '=', $this->getQualifiedLocalKeyName());
        }, null, null, $type);

        return $this;
    }

    /**
     * Get the qualified second local key on the through parent model.
     *
     * @return string
     */
    public function getQualifiedSecondLocalKeyName()
    {
        return $this->throughParent->qualifyColumn($this->secondLocalKey);
    }
}