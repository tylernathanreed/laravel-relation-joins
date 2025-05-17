<?php

namespace Tests\Unit;

use BadMethodCallException;
use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Models\EloquentCountryModelStub;
use Tests\Models\EloquentPostModelStub;
use Tests\Models\EloquentSoftDeletingCountryModelStub;
use Tests\Models\EloquentSoftDeletingUserModelStub;
use Tests\Models\EloquentUserModelStub;

class HasManyThroughTest extends TestCase
{
    #[Test]
    #[DataProvider('queryDataProvider')]
    public function basic(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCountryModelStub)
            ->joinRelation('posts');

        $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" inner join "posts" on "posts"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function inverse(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)
            ->joinRelation('user.country');

        $this->assertEquals('select * from "posts" inner join "users" on "users"."id" = "posts"."user_id" inner join "countries" on "countries"."id" = "users"."country_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function alias_far(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCountryModelStub)
            ->joinRelation('posts as articles');

        $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" inner join "posts" as "articles" on "articles"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function alias_through(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCountryModelStub)
            ->joinRelation('posts as citizens,posts');

        $this->assertEquals('select * from "countries" inner join "users" as "citizens" on "citizens"."country_id" = "countries"."id" inner join "posts" on "posts"."user_id" = "citizens"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function alias_multiple(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCountryModelStub)
            ->joinRelation('posts as citizens,articles');

        $this->assertEquals('select * from "countries" inner join "users" as "citizens" on "citizens"."country_id" = "countries"."id" inner join "posts" as "articles" on "articles"."user_id" = "citizens"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function alias_multiple_nested(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCountryModelStub)
            ->joinRelation('posts as citizens,articles.likes as feedback,favorites');

        $this->assertEquals('select * from "countries" inner join "users" as "citizens" on "citizens"."country_id" = "countries"."id" inner join "posts" as "articles" on "articles"."user_id" = "citizens"."id" inner join "comments" as "feedback" on "feedback"."post_id" = "articles"."id" inner join "likes" as "favorites" on "favorites"."comment_id" = "feedback"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function soft_deletes_parent(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentSoftDeletingCountryModelStub)
            ->joinRelation('posts');

        $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" inner join "posts" on "posts"."user_id" = "users"."id" where "countries"."deleted_at" is null', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function soft_deletes_through(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCountryModelStub)
            ->joinRelation('postsThroughSoftDeletingUser');

        $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" and "users"."deleted_at" is null inner join "posts" on "posts"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function soft_deletes_child(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCountryModelStub)
            ->joinRelation('softDeletingPosts');

        $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" inner join "posts" on "posts"."user_id" = "users"."id" and "posts"."deleted_at" is null', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function circular(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('employeePosts');

        $this->assertEquals('select * from "users" inner join "users" as "self_alias_hash" on "self_alias_hash"."manager_id" = "users"."id" inner join "posts" on "posts"."user_id" = "self_alias_hash"."id"', preg_replace('/\b(laravel_reserved_\d)(\b|$)/i', 'self_alias_hash', $builder->toSql()));
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function circular_alias(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('employeePosts as employees,posts');

        $this->assertEquals('select * from "users" inner join "users" as "employees" on "employees"."manager_id" = "users"."id" inner join "posts" on "posts"."user_id" = "employees"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function circular_alias_soft_deletes(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentSoftDeletingUserModelStub)
            ->joinRelation('employeePosts as employees,posts');

        $this->assertEquals('select * from "users" inner join "users" as "employees" on "employees"."manager_id" = "users"."id" and "employees"."deleted_at" is null inner join "posts" on "posts"."user_id" = "employees"."id" where "users"."deleted_at" is null', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function through_circular(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('departmentEmployees');

        $this->assertEquals('select * from "users" inner join "departments" on "departments"."supervisor_id" = "users"."id" inner join "users" as "self_alias_hash" on "self_alias_hash"."department_id" = "departments"."id"', preg_replace('/\b(laravel_reserved_\d)(\b|$)/i', 'self_alias_hash', $builder->toSql()));
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function through_circular_alias_soft_deletes(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('employeesThroughSoftDeletingDepartment as employees');

        $this->assertEquals('select * from "users" inner join "departments" on "departments"."supervisor_id" = "users"."id" and "departments"."deleted_at" is null inner join "users" as "employees" on "employees"."department_id" = "departments"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function left_join(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCountryModelStub)
            ->leftJoinRelation('posts');

        $this->assertEquals('select * from "countries" left join "users" on "users"."country_id" = "countries"."id" left join "posts" on "posts"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function left_join_inverse(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)
            ->leftJoinRelation('user.country');

        $this->assertEquals('select * from "posts" left join "users" on "users"."id" = "posts"."user_id" left join "countries" on "countries"."id" = "users"."country_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function constraints(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCountryModelStub)
            ->joinRelation('posts', function ($join) {
                $join->on('posts.language', '=', 'country.primary_language');
            });

        $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" inner join "posts" on "posts"."user_id" = "users"."id" and "posts"."language" = "country"."primary_language"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function constraints_pivot(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCountryModelStub)
            ->joinRelation('posts', function ($join, $through) {
                $through->where('users.is_admin', '=', false);
            });

        $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" and "users"."is_admin" = ? inner join "posts" on "posts"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals([false], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function constraints_pivot_scope(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCountryModelStub)
            ->joinRelation('posts', function ($join, $through) {
                $through->active();
            });

        $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" and "active" = ? inner join "posts" on "posts"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals([true], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function constraints_pivot_soft_deletes(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCountryModelStub)
            ->joinRelation('postsThroughSoftDeletingUser', function ($join, $through) {
                $through->active();
            });

        $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" and "active" = ? and "users"."deleted_at" is null inner join "posts" on "posts"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals([true], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function constraints_pivot_soft_deletes_with_trashed(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCountryModelStub)
            ->joinRelation('postsThroughSoftDeletingUser', function ($join, $through) {
                $through->withTrashed();
            });

        $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" inner join "posts" on "posts"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function constraints_pivot_soft_deletes_alias(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCountryModelStub)
            ->joinRelation('postsThroughSoftDeletingUser as citizens,articles');

        $this->assertEquals('select * from "countries" inner join "users" as "citizens" on "citizens"."country_id" = "countries"."id" and "citizens"."deleted_at" is null inner join "posts" as "articles" on "articles"."user_id" = "citizens"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function constraints_pivot_soft_deletes_with_trashed_alias(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCountryModelStub)
            ->joinRelation('postsThroughSoftDeletingUser as citizens,articles', function ($join, $through) {
                $through->withTrashed();
            });

        $this->assertEquals('select * from "countries" inner join "users" as "citizens" on "citizens"."country_id" = "countries"."id" inner join "posts" as "articles" on "articles"."user_id" = "citizens"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function constraints_pivot_missing_method(Closure $query, string $builderClass)
    {
        $this->expectException(BadMethodCallException::class);

        $builder = $query(new EloquentCountryModelStub)
            ->joinRelation('posts', function ($join, $through) {
                $through->missingMethod();
            });
    }
}
