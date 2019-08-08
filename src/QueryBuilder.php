<?php

namespace Reedware\LaravelRelationJoins;

use Illuminate\Database\Query\Builder as Query;

class QueryBuilder extends Query
{
    use Concerns\ForwardsParentCalls;
    use Concerns\MergeJoins;

}