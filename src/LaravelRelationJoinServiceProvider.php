<?php

namespace Reedware\LaravelRelationJoins;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Query\Builder as Query;
use Illuminate\Database\Eloquent\Builder as Eloquent;
use Illuminate\Database\Eloquent\Relations\Relation as LaravelRelation;

class LaravelRelationJoinServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->bootMissingMethodsAsMacros(Query::class, QueryBuilder::class);
        $this->bootMissingMethodsAsMacros(Eloquent::class, EloquentBuilder::class);
        $this->bootMissingMethodsAsMacros(LaravelRelation::class, Relation::class);
    }

    /**
     * Registers the missing methods on the parent as macros to the child.
     *
     * @param  string  $parent
     * @param  string  $child
     *
     * @return void
     */
    protected function bootMissingMethodsAsMacros($parent, $child)
    {
        $except = [
            'newFromParent',
            'inheritProperties',
            'callFromParent'
        ];

        $missing = array_values(array_diff(get_class_methods($child), get_class_methods($parent)));

        $macros = array_values(array_diff($missing, $except));

        foreach($macros as $macro) {
            $parent::macro($macro, function(...$arguments) use ($child, $macro) {
                return $child::callFromParent($this, $macro, $arguments);
            });
        }
    }
}