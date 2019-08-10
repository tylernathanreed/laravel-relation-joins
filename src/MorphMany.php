<?php

namespace Reedware\LaravelRelationJoins;

use Illuminate\Database\Eloquent\Relations\MorphMany as Relation;

class MorphMany extends Relation
{
	use Concerns\ForwardsMorphManyCalls;
}