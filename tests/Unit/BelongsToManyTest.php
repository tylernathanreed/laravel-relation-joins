<?php

namespace Reedware\LaravelRelationJoins\Tests\Unit;

use Closure;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentRoleModelStub;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentSoftDeletingUserModelStub;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentUserModelStub;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentVideoModelStub;

class BelongsToManyTest extends TestCase
{
    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function basic(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('roles');

        $this->assertEquals('select * from "users" inner join "role_user" on "role_user"."user_id" = "users"."id" inner join "roles" on "roles"."id" = "role_user"."role_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function inverse(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentRoleModelStub)
            ->joinRelation('users');

        $this->assertEquals('select * from "roles" inner join "role_user" on "role_user"."role_id" = "roles"."id" inner join "users" on "users"."id" = "role_user"."user_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function asMorph(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentVideoModelStub)
            ->joinRelation('videoTags');

        $this->assertEquals('select * from "videos" inner join "taggables" on "taggables"."taggable_id" = "videos"."id" inner join "tags" on "tags"."id" = "taggables"."tag_id" and "taggables"."taggable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentVideoModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function alias_child(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('roles as positions');

        $this->assertEquals('select * from "users" inner join "role_user" on "role_user"."user_id" = "users"."id" inner join "roles" as "positions" on "positions"."id" = "role_user"."role_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function alias_pivot(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('roles as users_roles,roles');

        $this->assertEquals('select * from "users" inner join "role_user" as "users_roles" on "users_roles"."user_id" = "users"."id" inner join "roles" on "roles"."id" = "users_roles"."role_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function alias_multiple(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('roles as position_user,positions');

        $this->assertEquals('select * from "users" inner join "role_user" as "position_user" on "position_user"."user_id" = "users"."id" inner join "roles" as "positions" on "positions"."id" = "position_user"."role_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function softDeletes_parent(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentSoftDeletingUserModelStub)
            ->joinRelation('roles');

        $this->assertEquals('select * from "users" inner join "role_user" on "role_user"."user_id" = "users"."id" inner join "roles" on "roles"."id" = "role_user"."role_id" where "users"."deleted_at" is null', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function softDeletes_child(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('softDeletingRoles');

        $this->assertEquals('select * from "users" inner join "role_user" on "role_user"."user_id" = "users"."id" inner join "roles" on "roles"."id" = "role_user"."role_id" and "roles"."deleted_at" is null', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function circular(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('messagedUsers');

        $this->assertEquals('select * from "users" inner join "messages" on "messages"."from_user_id" = "users"."id" inner join "users" as "self_alias_hash" on "self_alias_hash"."id" = "messages"."to_user_id"', preg_replace('/\b(laravel_reserved_\d)(\b|$)/i', 'self_alias_hash', $builder->toSql()));
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function circular_childAlias(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('messagedUsers as recipients');

        $this->assertEquals('select * from "users" inner join "messages" on "messages"."from_user_id" = "users"."id" inner join "users" as "recipients" on "recipients"."id" = "messages"."to_user_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function leftJoin(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->leftJoinRelation('roles');

        $this->assertEquals('select * from "users" left join "role_user" on "role_user"."user_id" = "users"."id" left join "roles" on "roles"."id" = "role_user"."role_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function leftJoin_inverse(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentRoleModelStub)
            ->leftJoinRelation('users');

        $this->assertEquals('select * from "roles" left join "role_user" on "role_user"."role_id" = "roles"."id" left join "users" on "users"."id" = "role_user"."user_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function constraints(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('roles', function ($join) {
                $join->where('roles.name', '=', 'admin');
            });

        $this->assertEquals('select * from "users" inner join "role_user" on "role_user"."user_id" = "users"."id" inner join "roles" on "roles"."id" = "role_user"."role_id" and "roles"."name" = ?', $builder->toSql());
        $this->assertEquals(['admin'], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function constraints_pivot(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('roles', function ($join, $pivot) {
                $pivot->where('role_user.domain', '=', 'web');
            });

        $this->assertEquals('select * from "users" inner join "role_user" on "role_user"."user_id" = "users"."id" and "role_user"."domain" = ? inner join "roles" on "roles"."id" = "role_user"."role_id"', $builder->toSql());
        $this->assertEquals(['web'], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function constraints_pivot_nested(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('roles', function ($join, $pivot) {
                $pivot->where('role_user.domain', '=', 'web');
                $pivot->where(function ($pivot) {
                    $pivot->where('role_user.application', 'main');
                    $pivot->orWhere('role_user.global', true);
                });
            });

        $this->assertEquals('select * from "users" inner join "role_user" on "role_user"."user_id" = "users"."id" and "role_user"."domain" = ? and ("role_user"."application" = ? or "role_user"."global" = ?) inner join "roles" on "roles"."id" = "role_user"."role_id"', $builder->toSql());
        $this->assertEquals(['web', 'main', true], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function constraints_pivot_model(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('rolesUsingPivotModel', function ($join, $pivot) {
                $pivot->where('role_user.domain', '=', 'web');
            });

        $this->assertEquals('select * from "users" inner join "role_user" on "role_user"."user_id" = "users"."id" and "role_user"."domain" = ? inner join "roles" on "roles"."id" = "role_user"."role_id"', $builder->toSql());
        $this->assertEquals(['web'], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function constraints_pivot_model_nested(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('rolesUsingPivotModel', function ($join, $pivot) {
                $pivot->where('role_user.domain', '=', 'web');
                $pivot->where(function ($pivot) {
                    $pivot->where('role_user.application', 'main');
                    $pivot->orWhere('role_user.global', true);
                });
            });

        $this->assertEquals('select * from "users" inner join "role_user" on "role_user"."user_id" = "users"."id" and "role_user"."domain" = ? and ("role_user"."application" = ? or "role_user"."global" = ?) inner join "roles" on "roles"."id" = "role_user"."role_id"', $builder->toSql());
        $this->assertEquals(['web', 'main', true], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function constraints_pivot_model_scope(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('rolesUsingPivotModel', function ($join, $pivot) {
                $pivot->web();
            });

        $this->assertEquals('select * from "users" inner join "role_user" on "role_user"."user_id" = "users"."id" and "domain" = ? inner join "roles" on "roles"."id" = "role_user"."role_id"', $builder->toSql());
        $this->assertEquals(['web'], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function constraints_pivot_model_softDeletes(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('rolesUsingSoftDeletingPivotModel');

        $this->assertEquals('select * from "users" inner join "role_user" on "role_user"."user_id" = "users"."id" and "role_user"."deleted_at" is null inner join "roles" on "roles"."id" = "role_user"."role_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    /**
     * @test
     * @dataProvider queryDataProvider
     */
    public function constraints_pivot_model_softDeletes_withTrashed(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('rolesUsingSoftDeletingPivotModel', function ($join, $pivot) {
                $pivot->withTrashed();
            });

        $this->assertEquals('select * from "users" inner join "role_user" on "role_user"."user_id" = "users"."id" inner join "roles" on "roles"."id" = "role_user"."role_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }
}
