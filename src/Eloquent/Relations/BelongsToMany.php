<?php

namespace Reedware\LaravelRelationJoins\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as Relation;

class BelongsToMany extends Relation
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
        if (strpos($alias, ',') !== false) {
            [$pivotAlias, $farAlias] = explode(',', $alias);
        } else {
            [$pivotAlias, $farAlias] = [null, $alias];
        }

        if (is_null($farAlias) && $parentQuery->getQuery()->from === $query->getQuery()->from) {
            $farAlias = $this->getRelationCountHash();
        }

        if (is_null($pivotAlias) && $parentQuery->getQuery()->from === $this->table) {
            $pivotAlias = $this->getRelationCountHash();
        }

        if (! is_null($farAlias) && $farAlias != $this->related->getTable()) {
            $query->from($this->related->getTable().' as '.$farAlias);

            $this->related->setTable($farAlias);
        }

        if (! is_null($pivotAlias) && $pivotAlias != $this->table) {
            $table = $this->table.' as '.$pivotAlias;

            $on = $pivotAlias;
        } else {
            $table = $on = $this->table;
        }

        $this->performRelationJoin($table, $on, $query, $type);

        return $query->whereColumn(
            $this->getQualifiedRelatedKeyName(), '=', $on.'.'.$this->relatedPivotKey
        );
    }

    /**
     * Set the join clause for the relation query.
     *
     * @param  string  $table
     * @param  string  $on
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @param  string  $type
     *
     * @return $this
     */
    protected function performRelationJoin($table, $on, $query = null, $type = 'inner')
    {
        $query = $query ?: $this->query;

        $query->join($table, $on.'.'.$this->foreignPivotKey, '=', $this->getQualifiedParentKeyName(), $type);

        return $this;
    }

    /**
     * Get the fully qualified related key name for the relation.
     *
     * @return string
     */
    public function getQualifiedRelatedKeyName()
    {
        return $this->related->qualifyColumn($this->relatedKey);
    }
}