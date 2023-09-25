<?php

namespace Reedware\LaravelRelationJoins;

class MorphTypes
{
    /**
     * The underlying morph types.
     *
     * @var array<string>
     */
    public array $items;

    /**
     * Creates a new morph types instance.
     *
     * @param  string|array<string>  $items
     * @return $this
     */
    public function __construct($items)
    {
        $this->items = (array) $items;
    }
}
