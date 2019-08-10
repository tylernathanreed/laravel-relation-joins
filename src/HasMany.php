<?php

namespace Reedware\LaravelRelationJoins;

use Illuminate\Database\Eloquent\Relations\HasMany as Relation;

class HasMany extends Relation
{
	use Concerns\ForwardsHasManyCalls;
}