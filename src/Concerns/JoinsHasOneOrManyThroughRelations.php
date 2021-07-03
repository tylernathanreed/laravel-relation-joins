<?php

namespace Reedware\LaravelRelationJoins\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait JoinsHasOneOrManyThroughRelations
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
            $this->getQualifiedForeignKeyName(), '=', $on.'.'.$this->secondLocalKey
        );
    }
}
