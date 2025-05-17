<?php

namespace Tests\Unit;

use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Models\EloquentUserModelStub;

class JoinOperationsTest extends TestCase
{
    #[Test]
    #[DataProvider('queryDataProvider')]
    public function on(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)->joinRelation('phone', function ($join) {
            $join->on('phones.extra', '=', 'users.extra');
        });

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id" and "phones"."extra" = "users"."extra"', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function orOn(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)->joinRelation('phone', function ($join) {
            $join->on('phones.extra_1', '=', 'users.extra_1');
            $join->orOn('phones.extra_2', '=', 'users.extra_2');
        });

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id" and ("phones"."extra_1" = "users"."extra_1" or "phones"."extra_2" = "users"."extra_2")', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }

    #[Test]
    #[DataProvider('queryDataProvider')]
    public function nested(Closure $query, string $builderClass)
    {
        $builder = $query(new EloquentUserModelStub)->joinRelation('phone', function ($join) {
            $join->on(function ($join) {
                $join->on('phones.extra_1', '=', 'users.extra_1');
                $join->orOn('phones.extra_2', '=', 'users.extra_2');
            });
        });

        $this->assertEquals('select * from "users" inner join "phones" on "phones"."user_id" = "users"."id" and ("phones"."extra_1" = "users"."extra_1" or "phones"."extra_2" = "users"."extra_2")', $builder->toSql());
        $this->assertEquals($builderClass, get_class($builder));
    }
}
