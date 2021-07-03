<?php

namespace Reedware\LaravelRelationJoins;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Query\Builder as Query;
use Illuminate\Database\Eloquent\Builder as Eloquent;
use Illuminate\Database\Eloquent\Relations\Relation;

class LaravelRelationJoinServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Query::mixin(new Mixins\MergeJoins);
        Eloquent::mixin(new Mixins\JoinsRelationships);
        Eloquent::mixin(new Mixins\JoinOperations);
        Relation::mixin(new Mixins\RelationJoinQueries);
    }
}