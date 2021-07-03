<?php

namespace Reedware\LaravelRelationJoins\Tests\Unit;

use Closure;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentSupplierModelStub;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentUserHistoryModelStub;

class HasOneThroughTest extends TestCase
{
    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function basic(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentSupplierModelStub)
            ->joinRelation('userHistory');

        $this->assertEquals('select * from "suppliers" inner join "users" on "users"."supplier_id" = "suppliers"."id" inner join "history" on "history"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function inverse(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserHistoryModelStub)
            ->joinRelation('user.supplier');

        $this->assertEquals('select * from "history" inner join "users" on "users"."id" = "history"."user_id" inner join "suppliers" on "suppliers"."id" = "users"."supplier_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function alias_far(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentSupplierModelStub)
            ->joinRelation('userHistory as revisions');

        $this->assertEquals('select * from "suppliers" inner join "users" on "users"."supplier_id" = "suppliers"."id" inner join "history" as "revisions" on "revisions"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function alias_through(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentSupplierModelStub)
            ->joinRelation('userHistory as workers,history');

        $this->assertEquals('select * from "suppliers" inner join "users" as "workers" on "workers"."supplier_id" = "suppliers"."id" inner join "history" on "history"."user_id" = "workers"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function alias_multiple(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentSupplierModelStub)
            ->joinRelation('userHistory as workers,revisions');

        $this->assertEquals('select * from "suppliers" inner join "users" as "workers" on "workers"."supplier_id" = "suppliers"."id" inner join "history" as "revisions" on "revisions"."user_id" = "workers"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function inverse_alias_far(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserHistoryModelStub)
            ->joinRelation('user.supplier as providers');

        $this->assertEquals('select * from "history" inner join "users" on "users"."id" = "history"."user_id" inner join "suppliers" as "providers" on "providers"."id" = "users"."supplier_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function inverse_alias_through(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserHistoryModelStub)
            ->joinRelation('user as workers.supplier');

        $this->assertEquals('select * from "history" inner join "users" as "workers" on "workers"."id" = "history"."user_id" inner join "suppliers" on "suppliers"."id" = "workers"."supplier_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function inverse_alias_multiple(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserHistoryModelStub)
            ->joinRelation('user as workers.supplier as providers');

        $this->assertEquals('select * from "history" inner join "users" as "workers" on "workers"."id" = "history"."user_id" inner join "suppliers" as "providers" on "providers"."id" = "workers"."supplier_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function leftJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentSupplierModelStub)
            ->leftJoinRelation('userHistory');

        $this->assertEquals('select * from "suppliers" left join "users" on "users"."supplier_id" = "suppliers"."id" left join "history" on "history"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function leftJoin_inverse(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserHistoryModelStub)
            ->leftJoinRelation('user.supplier');

        $this->assertEquals('select * from "history" left join "users" on "users"."id" = "history"."user_id" left join "suppliers" on "suppliers"."id" = "users"."supplier_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }
}