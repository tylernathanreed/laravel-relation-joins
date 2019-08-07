<?php

namespace Reedware\LaravelRelationJoins\Relations;

class MorphToMany extends BelongsToMany
{
    /**
     * Set the join clause for the relation query.
     *
     * @param  string  $table
     * @param  string  $on
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @param  string  $type
     * @return $this
     */
    protected function performRelationJoin($table, $on, $query = null, $type = 'inner')
    {
        $query = $query ?: $this->query;

        $query->join($table, function ($join) use ($on) {
            $join->on($on.'.'.$this->foreignPivotKey, '=', $this->getQualifiedParentKeyName());

            $join->where($on.'.'.$this->morphType, '=', $this->morphClass);
        }, null, null, $type);

        return $this;
    }
}