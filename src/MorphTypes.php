<?php

namespace Reedware\LaravelRelationJoins;

class MorphTypes
{
    /**
     * The underlying morph types.
     *
     * @var array
     */
    public $items;

    /**
     * Creates a new morph types instance.
     *
     * @param  string|array  $items
     *
     * @return $this;
     */
    public function __construct($items)
    {
        $this->items = (array) $items;
    }
}
