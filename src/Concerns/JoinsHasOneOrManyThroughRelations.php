<?php

namespace Reedware\LaravelRelationJoins\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Reedware\LaravelRelationJoins\Qualify;

trait JoinsHasOneOrManyThroughRelations
{
    /**
     * The count of self joins.
     *
     * @var int
     */
    protected static $selfJoinCount = 0;

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
            $join->on($on.'.'.$this->firstKey, '=', Qualify::column($parentQuery, $this->localKey));

            if ($this->throughParentSoftDeletes()) {
                $join->whereNull($on.'.'.$this->throughParent->getDeletedAtColumn());
            }
        }, null, null, $type);

        return $query->whereColumn(
            $this->getQualifiedForeignKeyName(), '=', $on.'.'.$this->secondLocalKey
        );
    }

    /**
     * Get a relationship join table hash.
     *
     * @return string
     */
    public function getRelationCountHash($incrementJoinCount = true)
    {
        return 'laravel_reserved_'.($incrementJoinCount ? static::$selfJoinCount++ : static::$selfJoinCount);
    }
}
