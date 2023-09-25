<?php

namespace Reedware\LaravelRelationJoins\Mixins;

use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;

/** @mixin Builder */
class MergeJoins
{
    /**
     * Defines the mixin for {@see $query->mergeJoins()}.
     */
    public function mergeJoins(): Closure
    {
        /**
         * Merges an array of join clauses and bindings.
         *
         * @param  array  $joins
         * @param  array  $bindings
         * @return void
         */
        return function ($joins, $bindings) {
            /** @var Builder $this */
            $this->joins = array_merge($this->joins ?: [], (array) $joins);

            $this->bindings['join'] = array_values(
                array_merge($this->bindings['join'], (array) $bindings)
            );
        };
    }

    /**
     * Defines the mixin for {@see $query->replaceWhereNestedQueryBuildersWithJoinBuilders()}.
     */
    public function replaceWhereNestedQueryBuildersWithJoinBuilders(): Closure
    {
        /**
         * Replaces the query builders in nested "where" clauses with join builders.
         *
         * @param  \Illuminate\Database\Query\Builder  $query
         * @return void
         */
        return function ($query) {
            /** @var Builder $this */
            $wheres = $query->wheres;

            $wheres = array_map(function ($where) {
                if (!isset($where['query'])) {
                    return $where;
                }

                if ($where['type'] == 'Exists' || $where['type'] == 'NotExists') {
                    return $where;
                }

                $this->replaceWhereNestedQueryBuildersWithJoinBuilders($where['query']);

                $joinClause = new JoinClause($where['query'], 'inner', $where['query']->from);

                foreach (array_keys(get_object_vars($where['query'])) as $key) {
                    $joinClause->{$key} = $where['query']->{$key};
                }

                $where['query'] = $joinClause;

                return $where;
            }, $wheres);

            $query->wheres = $wheres;
        };
    }
}
