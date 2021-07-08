<?php

namespace Reedware\LaravelRelationJoins\Tests\Unit;

use Closure;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentPhoneModelStub;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentPostModelStub;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentSoftDeletingPhoneModelStub;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentSoftDeletingUserModelStub;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentUserModelStub;

class HasOneTest extends TestCase
{
    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function basic(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('phone');

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function inverse(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPhoneModelStub)
            ->joinRelation('user');

        $this->assertEquals('select * from "phones" inner join "users" on "users"."id" = "phones"."user_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function inverse_alias(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPhoneModelStub)
            ->joinRelation('user as contacts');

        $this->assertEquals('select * from "phones" inner join "users" as "contacts" on "contacts"."id" = "phones"."user_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function asMorph(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)
            ->joinRelation('postImage');

        $this->assertEquals('select * from "posts" inner join "images" on "images"."imageable_id" = "posts"."id" and "imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function alias_child(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('phone as telephones');

        $this->assertEquals('select * from "users" inner join "phones" as "telephones" on "telephones"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function softDeletes_parent(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentSoftDeletingUserModelStub)
            ->joinRelation('phone');

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id" where "users"."deleted_at" is null', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function softDeletes_parent_withTrashed(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentSoftDeletingUserModelStub)
            ->joinRelation('phone')
            ->withTrashed();

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function softDeletes_parent_inverse(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPhoneModelStub)
            ->joinRelation('softDeletingUser');

        $this->assertEquals('select * from "phones" inner join "users" on "users"."id" = "phones"."user_id" and "users"."deleted_at" is null', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function softDeletes_child(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('softDeletingPhone');

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id" and "phones"."deleted_at" is null', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function softDeletes_child_withTrashed(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('softDeletingPhone', function ($join) {
                $join->withTrashed();
            });

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function softDeletes_child_inverse(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentSoftDeletingPhoneModelStub)
            ->joinRelation('user');

        $this->assertEquals('select * from "phones" inner join "users" on "users"."id" = "phones"."user_id" where "phones"."deleted_at" is null', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function softDeletes(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentSoftDeletingUserModelStub)
            ->joinRelation('softDeletingPhone');

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id" and "phones"."deleted_at" is null where "users"."deleted_at" is null', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function softDeletes_withParentTrashed(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentSoftDeletingUserModelStub)
            ->joinRelation('softDeletingPhone')
            ->withTrashed();

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id" and "phones"."deleted_at" is null', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function softDeletes_withChildTrashed(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentSoftDeletingUserModelStub)
            ->joinRelation('softDeletingPhone', function ($join) {
                $join->withTrashed();
            });

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id" where "users"."deleted_at" is null', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function softDeletes_withTrashed(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentSoftDeletingUserModelStub)
            ->withTrashed()
            ->joinRelation('softDeletingPhone', function ($join) {
                $join->withTrashed();
            });

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function leftJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->leftJoinRelation('phone');

        $this->assertEquals('select * from "users" left join "phones" on "phones"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function leftJoin_inverse(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPhoneModelStub)
            ->leftJoinRelation('user');

        $this->assertEquals('select * from "phones" left join "users" on "users"."id" = "phones"."user_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function rightJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->rightJoinRelation('phone');

        $this->assertEquals('select * from "users" right join "phones" on "phones"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function crossJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->crossJoinRelation('phone');

        $this->assertEquals('select * from "users" cross join "phones" on "phones"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }
}
