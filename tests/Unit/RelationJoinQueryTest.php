<?php

namespace Reedware\LaravelRelationJoins\Tests\Unit;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Reedware\LaravelRelationJoins\Tests\CustomRelation;
use Reedware\LaravelRelationJoins\Tests\Models\EloquentUserModelStub;

class RelationJoinQueryTest extends TestCase
{
    #[Test]
    public function non_relation()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(EloquentUserModelStub::class.'::active must return a relationship instance.');

        (new EloquentUserModelStub)
            ->useCustomBuilder(false)
            ->joinRelation('active');
    }

    #[Test]
    public function unsupported_relation()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported relation type ['.CustomRelation::class.'].');

        (new EloquentUserModelStub)
            ->useCustomBuilder(false)
            ->joinRelation('customRelation');
    }
}
