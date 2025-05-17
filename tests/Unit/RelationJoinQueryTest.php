<?php

namespace Tests\Unit;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\CustomRelation;
use Tests\Models\EloquentUserModelStub;

class RelationJoinQueryTest extends TestCase
{
    #[Test]
    public function nonRelation()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(EloquentUserModelStub::class.'::active must return a relationship instance.');

        (new EloquentUserModelStub)
            ->useCustomBuilder(false)
            ->joinRelation('active');
    }

    #[Test]
    public function unsupportedRelation()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported relation type ['.CustomRelation::class.'].');

        (new EloquentUserModelStub)
            ->useCustomBuilder(false)
            ->joinRelation('customRelation');
    }
}
