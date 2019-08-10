<?php

namespace Reedware\LaravelRelationJoins;

use Illuminate\Database\Eloquent\Relations\MorphOne as Relation;

class MorphOne extends Relation
{
	use Concerns\ForwardsMorphOneCalls;
}