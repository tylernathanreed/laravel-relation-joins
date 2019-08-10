<?php

namespace Reedware\LaravelRelationJoins;

use Illuminate\Database\Eloquent\Relations\BelongsTo as Relation;

class BelongsTo extends Relation
{
	use Concerns\ForwardsBelongsToCalls;
}