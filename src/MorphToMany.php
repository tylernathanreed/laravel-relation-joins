<?php

namespace Reedware\LaravelRelationJoins;

use Illuminate\Database\Eloquent\Relations\MorphToMany as Relation;

class MorphToMany extends Relation
{
    use Concerns\ForwardsMorphToManyCalls;

    /**
     * Get the fully qualified related key name for the relation.
     *
     * @return string
     */
    public function getQualifiedRelatedKeyName()
    {
        return method_exists($this->related, 'qualifyColumn')
            ? $this->related->qualifyColumn($this->relatedKey)
            : $this->related->getTable().'.'.$this->relatedKey;
    }
}