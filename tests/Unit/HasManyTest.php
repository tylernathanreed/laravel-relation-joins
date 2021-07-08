<?php

namespace Reedware\LaravelRelationJoins\Tests\Unit;

use Closure;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentCommentModelStub;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentCountryModelStub;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentPostModelStub;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentUserModelStub;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentVideoModelStub;

class HasManyTest extends TestCase
{
    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function basic(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)
            ->joinRelation('comments');

        $this->assertEquals('select * from "posts" inner join "comments" on "comments"."post_id" = "posts"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function inverse(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCommentModelStub)
            ->joinRelation('post');

        $this->assertEquals('select * from "comments" inner join "posts" on "posts"."id" = "comments"."post_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function inverse_alias(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCommentModelStub)
            ->joinRelation('post as article');

        $this->assertEquals('select * from "comments" inner join "posts" as "article" on "article"."id" = "comments"."post_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function asMorph(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentVideoModelStub)
            ->joinRelation('videoComments');

        $this->assertEquals('select * from "videos" inner join "comments" on "comments"."commentable_id" = "videos"."id" and "commentable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentVideoModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function childAlias(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)
            ->joinRelation('comments as feedback');

        $this->assertEquals('select * from "posts" inner join "comments" as "feedback" on "feedback"."post_id" = "posts"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function circular(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('employees');

        $this->assertEquals('select * from "users" inner join "users" as "self_alias_hash" on "self_alias_hash"."manager_id" = "users"."id"', preg_replace('/\b(laravel_reserved_\d)(\b|$)/i', 'self_alias_hash', $builder->toSql()));
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function circular_alias_child(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('employees as employees');

        $this->assertEquals('select * from "users" inner join "users" as "employees" on "employees"."manager_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function through(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('posts', function ($join) {
                $join->where('posts.is_active', '=', 1);
            })
            ->joinThroughRelation('posts.comments', function ($join) {
                $join->whereColumn('comments.created_by_id', '=', 'users.id');
            });

        $this->assertEquals('select * from "users" inner join "posts" on "posts"."user_id" = "users"."id" and "posts"."is_active" = ? inner join "comments" on "comments"."post_id" = "posts"."id" and "comments"."created_by_id" = "users"."id"', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function through_leftJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('posts', function ($join) {
                $join->where('posts.is_active', '=', 1);
            })
            ->leftJoinThroughRelation('posts.comments', function ($join) {
                $join->whereColumn('comments.created_by_id', '=', 'users.id');
            });

        $this->assertEquals('select * from "users" inner join "posts" on "posts"."user_id" = "users"."id" and "posts"."is_active" = ? left join "comments" on "comments"."post_id" = "posts"."id" and "comments"."created_by_id" = "users"."id"', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function through_rightJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('posts', function ($join) {
                $join->where('posts.is_active', '=', 1);
            })
            ->rightJoinThroughRelation('posts.comments', function ($join) {
                $join->whereColumn('comments.created_by_id', '=', 'users.id');
            });

        $this->assertEquals('select * from "users" inner join "posts" on "posts"."user_id" = "users"."id" and "posts"."is_active" = ? right join "comments" on "comments"."post_id" = "posts"."id" and "comments"."created_by_id" = "users"."id"', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function through_crossJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('posts', function ($join) {
                $join->where('posts.is_active', '=', 1);
            })
            ->crossJoinThroughRelation('posts.comments', function ($join) {
                $join->whereColumn('comments.created_by_id', '=', 'users.id');
            });

        $this->assertEquals('select * from "users" inner join "posts" on "posts"."user_id" = "users"."id" and "posts"."is_active" = ? cross join "comments" on "comments"."post_id" = "posts"."id" and "comments"."created_by_id" = "users"."id"', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function leftJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)
            ->leftJoinRelation('comments');

        $this->assertEquals('select * from "posts" left join "comments" on "comments"."post_id" = "posts"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function leftJoin_inverse(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCommentModelStub)
            ->leftJoinRelation('post');

        $this->assertEquals('select * from "comments" left join "posts" on "posts"."id" = "comments"."post_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function nested(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('posts.comments');

        $this->assertEquals('select * from "users" inner join "posts" on "posts"."user_id" = "users"."id" inner join "comments" on "comments"."post_id" = "posts"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function nested_alias_near(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('posts as articles.comments');

        $this->assertEquals('select * from "users" inner join "posts" as "articles" on "articles"."user_id" = "users"."id" inner join "comments" on "comments"."post_id" = "articles"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function nested_alias_far(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('posts.comments as reviews');

        $this->assertEquals('select * from "users" inner join "posts" on "posts"."user_id" = "users"."id" inner join "comments" as "reviews" on "reviews"."post_id" = "posts"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function nested_alias_multiple(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('posts as articles.comments as reviews');

        $this->assertEquals('select * from "users" inner join "posts" as "articles" on "articles"."user_id" = "users"."id" inner join "comments" as "reviews" on "reviews"."post_id" = "articles"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function localScope(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentCountryModelStub)
            ->joinRelation('users', function ($join) {
                $join->active();
            });

        $this->assertEquals('select * from "countries" inner join "users" on "users"."country_id" = "countries"."id" and "active" = ?', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function has(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('posts', function ($join) {
                $join->has('comments');
            });

        $this->assertEquals('select * from "users" inner join "posts" on "posts"."user_id" = "users"."id" and exists (select * from "comments" where "posts"."id" = "comments"."post_id")', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function doesntHave(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('posts', function ($join) {
                $join->doesntHave('comments');
            });

        $this->assertEquals('select * from "users" inner join "posts" on "posts"."user_id" = "users"."id" and not exists (select * from "comments" where "posts"."id" = "comments"."post_id")', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }
}
