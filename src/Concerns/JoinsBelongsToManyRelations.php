<?php

namespace Reedware\LaravelRelationJoins\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait JoinsBelongsToManyRelations
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

        $query->join($table, $on.'.'.$this->foreignPivotKey, '=', $this->getQualifiedParentKeyName(), $type);

        return $query->whereColumn(
            $this->getQualifiedRelatedKeyName(), '=', $on.'.'.$this->relatedPivotKey
        );
    }

    /**
     * Get the fully qualified related key name for the relation.
     *
     * @return string
     */
    public function getQualifiedRelatedKeyName()
    {
        return method_exists($this->related, 'qualifyColumn')
            ? $this->related->qualifyColumn($this->relatedKey)
            : $this->related->getTable().'.'.$this->relatedKey;
    }
}