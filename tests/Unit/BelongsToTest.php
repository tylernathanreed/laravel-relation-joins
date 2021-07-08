<?php

namespace Reedware\LaravelRelationJoins\Tests\Unit;

use Closure;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentPostModelStub;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentUserModelStub;

class BelongsToTest extends TestCase
{
    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function circular(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('manager');

        $this->assertEquals('select * from "users" inner join "users" as "self_alias_hash" on "self_alias_hash"."id" = "users"."manager_id"', preg_replace('/\b(laravel_reserved_\d)(\b|$)/i', 'self_alias_hash', $builder->toSql()));
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function circular_alias(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('manager as managers');

        $this->assertEquals('select * from "users" inner join "users" as "managers" on "managers"."id" = "users"."manager_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function nested_alias_multiple(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)
            ->joinRelation('user as authors.country as nations');

        $this->assertEquals('select * from "posts" inner join "users" as "authors" on "authors"."id" = "posts"."user_id" inner join "countries" as "nations" on "nations"."id" = "authors"."country_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function nested_constraints(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('supplier', function ($join) {
                $join->where(function ($join) {
                    $join->whereIn('supplier.state', ['AZ', 'CA', 'TX']);
                    $join->orWhere('supplier.has_international_restrictions', 1);
                });
            });

        $this->assertEquals('select * from "users" inner join "suppliers" on "suppliers"."id" = "users"."supplier_id" and ("supplier"."state" in (?, ?, ?) or "supplier"."has_international_restrictions" = ?)', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function nested_constraints_nested(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('supplier', function ($join) {
                $join->where(function ($join) {
                    $join->whereIn('supplier.state', ['AZ', 'CA', 'TX']);
                    $join->orWhere(function ($join) {
                        $join->where('supplier.has_international_restrictions', 1);
                        $join->where('supplier.country', '!=', 'US');
                    });
                });
            });

        $this->assertEquals('select * from "users" inner join "suppliers" on "suppliers"."id" = "users"."supplier_id" and ("supplier"."state" in (?, ?, ?) or ("supplier"."has_international_restrictions" = ? and "supplier"."country" != ?))', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }
}
