<?php

namespace Reedware\LaravelRelationJoins;

use Illuminate\Database\Eloquent\Relations\HasOne as Relation;

class HasOne extends Relation
{
	use Concerns\ForwardsHasOneCalls;
}