<?php

namespace Reedware\LaravelRelationJoins;

use Illuminate\Database\Eloquent\Builder as Eloquent;

class Builder extends Eloquent
{
	use Concerns\JoinsRelationships;
}