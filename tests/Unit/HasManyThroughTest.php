<?php

namespace Reedware\LaravelRelationJoins\Tests\Unit;

use Closure;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentCountryModelStub;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentPostModelStub;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentSoftDeletingCountryModelStub;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentSoftDeletingUserModelStub;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentUserModelStub;

class HasManyThroughTest extends TestCase
{
    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function basic(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCountryModelStub)
            ->joinRelation('posts');

        $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" inner join "posts" on "posts"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function inverse(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)
            ->joinRelation('user.country');

        $this->assertEquals('select * from "posts" inner join "users" on "users"."id" = "posts"."user_id" inner join "countries" on "countries"."id" = "users"."country_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function alias_far(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCountryModelStub)
            ->joinRelation('posts as articles');

        $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" inner join "posts" as "articles" on "articles"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function alias_through(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCountryModelStub)
            ->joinRelation('posts as citizens,posts');

        $this->assertEquals('select * from "countries" inner join "users" as "citizens" on "citizens"."country_id" = "countries"."id" inner join "posts" on "posts"."user_id" = "citizens"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function alias_multiple(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCountryModelStub)
            ->joinRelation('posts as citizens,articles');

        $this->assertEquals('select * from "countries" inner join "users" as "citizens" on "citizens"."country_id" = "countries"."id" inner join "posts" as "articles" on "articles"."user_id" = "citizens"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function alias_multiple_nested(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCountryModelStub)
            ->joinRelation('posts as citizens,articles.likes as feedback,favorites');

        $this->assertEquals('select * from "countries" inner join "users" as "citizens" on "citizens"."country_id" = "countries"."id" inner join "posts" as "articles" on "articles"."user_id" = "citizens"."id" inner join "comments" as "feedback" on "feedback"."post_id" = "articles"."id" inner join "likes" as "favorites" on "favorites"."comment_id" = "feedback"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function softDeletes_parent(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentSoftDeletingCountryModelStub)
            ->joinRelation('posts');

        $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" inner join "posts" on "posts"."user_id" = "users"."id" where "countries"."deleted_at" is null', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function softDeletes_through(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCountryModelStub)
            ->joinRelation('postsThroughSoftDeletingUser');

        if($this->isVersionAfter('7.10.0')) {
            $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" and "users"."deleted_at" is null inner join "posts" on "posts"."user_id" = "users"."id"', $builder->toSql());
        } else {
            $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" and "users"."deleted_at" is null inner join "posts" on "posts"."user_id" = "users"."id" and "users"."deleted_at" is null', $builder->toSql());
        }
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function softDeletes_child(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCountryModelStub)
            ->joinRelation('softDeletingPosts');

        $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" inner join "posts" on "posts"."user_id" = "users"."id" and "posts"."deleted_at" is null', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function circular(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('employeePosts');

        $this->assertEquals('select * from "users" inner join "users" as "self_alias_hash" on "self_alias_hash"."manager_id" = "users"."id" inner join "posts" on "posts"."user_id" = "self_alias_hash"."id"', preg_replace('/\b(laravel_reserved_\d)(\b|$)/i', 'self_alias_hash', $builder->toSql()));
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function circular_alias(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('employeePosts as employees,posts');

        $this->assertEquals('select * from "users" inner join "users" as "employees" on "employees"."manager_id" = "users"."id" inner join "posts" on "posts"."user_id" = "employees"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function circular_alias_softDeletes(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentSoftDeletingUserModelStub)
            ->joinRelation('employeePosts as employees,posts');

        if($this->isVersionAfter('7.10.0')) {
            $this->assertEquals('select * from "users" inner join "users" as "employees" on "employees"."manager_id" = "users"."id" and "employees"."deleted_at" is null inner join "posts" on "posts"."user_id" = "employees"."id" where "users"."deleted_at" is null', $builder->toSql());
        } else {
            $this->assertEquals('select * from "users" inner join "users" as "employees" on "employees"."manager_id" = "users"."id" and "employees"."deleted_at" is null inner join "posts" on "posts"."user_id" = "employees"."id" and "users"."deleted_at" is null where "users"."deleted_at" is null', $builder->toSql());
        }
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function throughCircular(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('departmentEmployees');

        $this->assertEquals('select * from "users" inner join "departments" on "departments"."supervisor_id" = "users"."id" inner join "users" as "self_alias_hash" on "self_alias_hash"."department_id" = "departments"."id"', preg_replace('/\b(laravel_reserved_\d)(\b|$)/i', 'self_alias_hash', $builder->toSql()));
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function throughCircular_alias_softDeletes(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('employeesThroughSoftDeletingDepartment as employees');

        if($this->isVersionAfter('7.10.0')) {
            $this->assertEquals('select * from "users" inner join "departments" on "departments"."supervisor_id" = "users"."id" and "departments"."deleted_at" is null inner join "users" as "employees" on "employees"."department_id" = "departments"."id"', $builder->toSql());
        } else {
            $this->assertEquals('select * from "users" inner join "departments" on "departments"."supervisor_id" = "users"."id" and "departments"."deleted_at" is null inner join "users" as "employees" on "employees"."department_id" = "departments"."id" and "departments"."deleted_at" is null', $builder->toSql());
        }
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function leftJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCountryModelStub)
            ->leftJoinRelation('posts');

        $this->assertEquals('select * from "countries" left join "users" on "users"."country_id" = "countries"."id" left join "posts" on "posts"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function leftJoin_inverse(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)
            ->leftJoinRelation('user.country');

        $this->assertEquals('select * from "posts" left join "users" on "users"."id" = "posts"."user_id" left join "countries" on "countries"."id" = "users"."country_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }
}