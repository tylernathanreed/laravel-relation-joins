<?php

namespace Tests\Unit;

use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Models\EloquentPhoneModelStub;
use Tests\Models\EloquentPostModelStub;
use Tests\Models\EloquentSoftDeletingPhoneModelStub;
use Tests\Models\EloquentSoftDeletingUserModelStub;
use Tests\Models\EloquentUserModelStub;

class HasOneTest extends TestCase
{
    #[Test]
    #[DataProvider('queryDataProvider')]
    public function basic(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('phone');

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function inverse(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPhoneModelStub)
            ->joinRelation('user');

        $this->assertEquals('select * from "phones" inner join "users" on "users"."id" = "phones"."user_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function inverse_alias(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPhoneModelStub)
            ->joinRelation('user as contacts');

        $this->assertEquals('select * from "phones" inner join "users" as "contacts" on "contacts"."id" = "phones"."user_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function as_morph(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPostModelStub)
            ->joinRelation('postImage');

        $this->assertEquals('select * from "posts" inner join "images" on "images"."imageable_id" = "posts"."id" and "imageable_type" = ?', $builder->toSql());
        $this->assertEquals([0 => EloquentPostModelStub::class], $builder->getBindings());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function alias_child(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('phone as telephones');

        $this->assertEquals('select * from "users" inner join "phones" as "telephones" on "telephones"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function soft_deletes_parent(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentSoftDeletingUserModelStub)
            ->joinRelation('phone');

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id" where "users"."deleted_at" is null', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function soft_deletes_parent_with_trashed(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentSoftDeletingUserModelStub)
            ->joinRelation('phone')
            ->withTrashed();

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function soft_deletes_parent_inverse(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPhoneModelStub)
            ->joinRelation('softDeletingUser');

        $this->assertEquals('select * from "phones" inner join "users" on "users"."id" = "phones"."user_id" and "users"."deleted_at" is null', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function soft_deletes_child(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('softDeletingPhone');

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id" and "phones"."deleted_at" is null', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function soft_deletes_child_with_trashed(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->joinRelation('softDeletingPhone', function ($join) {
                $join->withTrashed();
            });

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function soft_deletes_child_inverse(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentSoftDeletingPhoneModelStub)
            ->joinRelation('user');

        $this->assertEquals('select * from "phones" inner join "users" on "users"."id" = "phones"."user_id" where "phones"."deleted_at" is null', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function soft_deletes(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentSoftDeletingUserModelStub)
            ->joinRelation('softDeletingPhone');

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id" and "phones"."deleted_at" is null where "users"."deleted_at" is null', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function soft_deletes_with_parent_trashed(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentSoftDeletingUserModelStub)
            ->joinRelation('softDeletingPhone')
            ->withTrashed();

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id" and "phones"."deleted_at" is null', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function soft_deletes_with_child_trashed(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentSoftDeletingUserModelStub)
            ->joinRelation('softDeletingPhone', function ($join) {
                $join->withTrashed();
            });

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id" where "users"."deleted_at" is null', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function soft_deletes_with_trashed(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentSoftDeletingUserModelStub)
            ->withTrashed()
            ->joinRelation('softDeletingPhone', function ($join) {
                $join->withTrashed();
            });

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function left_join(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->leftJoinRelation('phone');

        $this->assertEquals('select * from "users" left join "phones" on "phones"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function left_join_inverse(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentPhoneModelStub)
            ->leftJoinRelation('user');

        $this->assertEquals('select * from "phones" left join "users" on "users"."id" = "phones"."user_id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function right_join(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->rightJoinRelation('phone');

        $this->assertEquals('select * from "users" right join "phones" on "phones"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function cross_join(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)
            ->crossJoinRelation('phone');

        $this->assertEquals('select * from "users" cross join "phones" on "phones"."user_id" = "users"."id"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }
}
