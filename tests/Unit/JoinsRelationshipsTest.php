<?php

namespace Reedware\LaravelRelationJoins\Tests\Unit;

use Closure;
use Illuminate\Database\Eloquent\Relations\Relation;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentCountryModelStub;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentUserModelStub;

class JoinsRelationshipsTest extends TestCase
{
    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function anonymousRelation(Closure $query, string $builderClass)
    {
        $relation = Relation::noConstraints(function () {
            return (new EloquentUserModelStub)
                ->belongsTo(EloquentCountryModelStub::class, 'country_name', 'name');
        });

        $builder = $query(new EloquentUserModelStub)
            ->joinRelation($relation);

        $this->assertEquals('select * from "users" inner join "countries" on "countries"."name" = "users"."country_name"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function anonymousRelation_alias(Closure $query, string $builderClass)
    {
        $relation = Relation::noConstraints(function () {
            return (new EloquentUserModelStub)
                ->belongsTo(EloquentCountryModelStub::class, 'kingdom_name', 'name');
        });

        $builder = $query(new EloquentUserModelStub)
            ->joinRelation([$relation, 'kingdoms']);

        $this->assertEquals('select * from "users" inner join "countries" as "kingdoms" on "kingdoms"."name" = "users"."kingdom_name"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }
}
