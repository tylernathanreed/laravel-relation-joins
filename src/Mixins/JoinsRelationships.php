<?php

namespace Reedware\LaravelRelationJoins\Mixins;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\JoinClause;
use LogicException;
use Reedware\LaravelRelationJoins\EloquentJoinClause;
use RuntimeException;

class JoinsRelationships
{
    /**
     * Defines the mixin for {@see $query->joinRelation()}.
     *
     * @return \Closure
     */
    public function joinRelation()
    {
        /**
         * Add a relationship join condition to the query.
         *
         * @param  string                                 $relationName
         * @param  \Closure|null                          $callback
         * @param  string                                 $type
         * @param  bool                                   $through
         * @param  \Illuminate\Database\Eloquent\Builder  $relatedQuery
         *
         * @return \Illuminate\Database\Eloquent\Builder|static
         */
        return function ($relationName, Closure $callback = null, $type = 'inner', $through = false, Builder $relatedQuery = null) {

            if (strpos($relationName, '.') !== false) {
                return $this->joinNestedRelation($relationName, $callback, $type, $through);
            }

            if (stripos($relationName, ' as ') !== false) {
                [$relationName, $alias] = preg_split('/\s+as\s+/i', $relationName);
            }

            $relation = ($relatedQuery ?: $this)->getRelationWithoutConstraints($relationName);

            if ($relation instanceof MorphTo) {
                throw new RuntimeException('joinRelation() does not support MorphTo relationships.');
            }

            if (! $relation instanceof Relation) {
                throw new LogicException(sprintf('%s::%s must return a relationship instance.', get_class($this->getModel()), $relationName));
            }

            $joinQuery = $relation->getRelationJoinQuery(
                $relation->getRelated()->newQuery(), $relatedQuery ?: $this, $type, $alias ?? null
            );

            // If we're simply passing through a relation, then we want to advance the relation
            // without actually applying any joins. Presumably the developer has already used
            // a modified version of this join, and they don't want to do it all over again.
            if ($through) {
                return $this->applyJoinScopes($joinQuery);
            }

            // Next we will call any given callback as an "anonymous" scope so they can get the
            // proper logical grouping of the where clauses if needed by this Eloquent query
            // builder. Then, we will be ready to finalize and return this query instance.
            if ($callback) {
                $this->callJoinScope($joinQuery, $callback);
            } else {
                $this->applyJoinScopes($joinQuery);
            }

            $this->addJoinRelationWhere(
                $joinQuery, $relation, $type
            );

            return ! is_null($relatedQuery) ? $joinQuery : $this;

        };
    }

    /**
     * Defines the mixin for {@see $query->joinNestedRelation()}.
     *
     * @return \Closure
     */
    protected function joinNestedRelation()
    {
        /**
         * Add nested relationship join conditions to the query.
         *
         * @param  string         $relations
         * @param  \Closure|null  $callback
         * @param  string         $type
         * @param  bool           $through
         *
         * @return \Illuminate\Database\Eloquent\Builder|static
         */
        return function ($relations, Closure $callback = null, $type = 'inner', $through = false) {

            $relations = explode('.', $relations);

            $relatedQuery = $this;

            while (count($relations) > 0) {
                $closure = count($relations) > 1 ? null : $callback;
                $useThrough = count($relations) > 1 && $through;

                $relatedQuery = $this->joinRelation(array_shift($relations), $closure, $type, $useThrough, $relatedQuery);
            }

            return $this;

        };
    }

    /**
     * Defines the mixin for {@see $query->applyJoinScopes()}.
     *
     * @return \Closure
     */
    protected function applyJoinScopes()
    {
        /**
         * Applies the eloquent scopes to the specified query.
         *
         * @param  \Illuminate\Database\Query\Builder  $joinQuery
         *
         * @return \Illuminate\Database\Query\Builder
         */
        return function (Builder $joinQuery) {

            $joins = $joinQuery->getQuery()->joins ?: [];

            foreach ($joins as $join) {
                if ($join instanceof EloquentJoinClause) {
                    $join->applyScopes();
                }
            }

            return $joinQuery;

        };
    }

    /**
     * Defines the mixin for {@see $query->callJoinScope()}.
     *
     * @return \Closure
     */
    protected function callJoinScope()
    {
        /**
         * Calls the provided callback on the join query.
         *
         * @param  \Illuminate\Database\Query\Builder  $joinQuery
         * @param  \Closure                            $callback
         *
         * @return void
         */
        return function (Builder $joinQuery, Closure $callback) {

            $joins = $joinQuery->getQuery()->joins ?: [];

            array_unshift($joins, $joinQuery);

            $queries = array_map(function ($join) {
                return $join instanceof JoinClause ? $join : $join->getQuery();
            }, $joins);

            // We will keep track of how many wheres are on each query before running the
            // scope so that we can properly group the added scope constraints in each
            // query as their own isolated nested where statement and avoid issues.

            $originalWhereCounts = array_map(function ($query) {
                return count($query->wheres ?: []);
            }, $queries);

            $callback(...$joins);

            $this->applyJoinScopes($joinQuery);

            foreach ($originalWhereCounts as $index => $count) {
                if (count($queries[$index]->wheres ?: []) > $count) {
                    $joinQuery->addNewWheresWithinGroup($queries[$index], $count);
                }
            }

            // Once the constraints have been applied, we'll need to shuffle arounds the
            // bindings so that the base query receives everything. We will apply all
            // of the bindings from the subsequent joins onto the first query.

            $joinQuery->getQuery()->bindings['join'] = [];

            array_shift($queries);

            foreach ($queries as $query) {
                $joinQuery->addBinding($query->getBindings(), 'join');
            }

        };
    }

    /**
     * Defines the mixin for {@see $query->addJoinRelationWhere()}.
     *
     * @return \Closure
     */
    protected function addJoinRelationWhere()
    {
        /**
         * Add the "join relation" condition where clause to the query.
         *
         * @param  \Illuminate\Database\Eloquent\Builder             $joinQuery
         * @param  \Illuminate\Database\Eloquent\Relations\Relation  $relation
         * @param  string                                            $type
         *
         * @return \Illuminate\Database\Eloquent\Builder|static
         */
        return function (Builder $joinQuery, Relation $relation, $type) {

            $joinQuery->mergeConstraintsFrom($relation->getQuery());

            $baseJoinQuery = $joinQuery->toBase();

            if (! empty($baseJoinQuery->joins)) {
                $this->mergeJoins($baseJoinQuery->joins, $baseJoinQuery->bindings['join']);
            }

            $this->join($baseJoinQuery->from, function ($join) use ($baseJoinQuery) {

                // There's an issue with mixing query builder where clauses
                // with join builder where clauses. To solve for this, we
                // have to recursively replace the nested where queries.

                $this->replaceWhereNestedQueryBuildersWithJoinBuilders($baseJoinQuery);

                $join->mergeWheres($baseJoinQuery->wheres, $baseJoinQuery->bindings['where']);

            }, null, null, $type);

            return $this;

        };
    }

    /**
     * Defines the mixin for {@see $query->replaceWhereNestedQueryBuildersWithJoinBuilders()}.
     *
     * @return \Closure
     */
    protected function replaceWhereNestedQueryBuildersWithJoinBuilders()
    {
        /**
         * Replaces the query builders in nested "where" clauses with join builders.
         *
         * @param  \Illuminate\Database\Query\Builder  $query
         *
         * @return void
         */
        return function ($query) {

            $wheres = $query->wheres;

            $wheres = array_map(function ($where) {
                if (! isset($where['query'])) {
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

    /**
     * Defines the mixin for {@see $query->leftJoinRelation()}.
     *
     * @return \Closure
     */
    public function leftJoinRelation()
    {
        /**
         * Add a relationship left join condition to the query.
         *
         * @param  string         $relation
         * @param  \Closure|null  $callback
         * @param  bool           $through
         *
         * @return \Illuminate\Database\Eloquent\Builder|static
         */
        return function ($relation, Closure $callback = null, $through = false) {
            return $this->joinRelation($relation, $callback, 'left', $through);
        };
    }

    /**
     * Defines the mixin for {@see $query->rightJoinRelation()}.
     *
     * @return \Closure
     */
    public function rightJoinRelation()
    {
        /**
         * Add a relationship right join condition to the query.
         *
         * @param  string         $relation
         * @param  \Closure|null  $callback
         * @param  bool           $through
         *
         * @return \Illuminate\Database\Eloquent\Builder|static
         */
        return function ($relation, Closure $callback = null, $through = false) {
            return $this->joinRelation($relation, $callback, 'right', $through);
        };
    }

    /**
     * Defines the mixin for {@see $query->crossJoinRelation()}.
     *
     * @return \Closure
     */
    public function crossJoinRelation()
    {
        /**
         * Add a relationship cross join condition to the query.
         *
         * @param  string         $relation
         * @param  \Closure|null  $callback
         * @param  bool           $through
         *
         * @return \Illuminate\Database\Eloquent\Builder|static
         */
        return function ($relation, Closure $callback = null, $through = false) {
            return $this->joinRelation($relation, $callback, 'cross', $through);
        };
    }

    /**
     * Defines the mixin for {@see $query->joinThroughRelation()}.
     *
     * @return \Closure
     */
    public function joinThroughRelation()
    {
        /**
         * Add a relationship join condition through a related model to the query.
         *
         * @param  string         $relation
         * @param  \Closure|null  $callback
         * @param  string         $type
         *
         * @return \Illuminate\Database\Eloquent\Builder|static
         */
        return function ($relation, Closure $callback = null, $type = 'inner') {
            return $this->joinRelation($relation, $callback, $type, true);
        };
    }

    /**
     * Defines the mixin for {@see $query->leftJoinThroughRelation()}.
     *
     * @return \Closure
     */
    public function leftJoinThroughRelation()
    {
        /**
         * Add a relationship left join condition through a related model to the query.
         *
         * @param  string         $relation
         * @param  \Closure|null  $callback
         *
         * @return \Illuminate\Database\Eloquent\Builder|static
         */
        return function ($relation, Closure $callback = null) {
            return $this->joinRelation($relation, $callback, 'left', true);
        };
    }

    /**
     * Defines the mixin for {@see $query->rightJoinThroughRelation()}.
     *
     * @return \Closure
     */
    public function rightJoinThroughRelation()
    {
        /**
         * Add a relationship right join condition through a related model to the query.
         *
         * @param  string         $relation
         * @param  \Closure|null  $callback
         *
         * @return \Illuminate\Database\Eloquent\Builder|static
         */
        return function ($relation, Closure $callback = null) {
            return $this->joinRelation($relation, $callback, 'right', true);
        };
    }

    /**
     * Defines the mixin for {@see $query->crossJoinThroughRelation()}.
     *
     * @return \Closure
     */
    public function crossJoinThroughRelation()
    {
        /**
         * Add a relationship cross join condition through a related model to the query.
         *
         * @param  string         $relation
         * @param  \Closure|null  $callback
         *
         * @return \Illuminate\Database\Eloquent\Builder|static
         */
        return function ($relation, Closure $callback = null) {
            return $this->joinRelation($relation, $callback, 'cross', true);
        };
    }
}
