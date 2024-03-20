<?php

namespace Reedware\LaravelRelationJoins;

use Illuminate\Database\Eloquent\Model;

class MorphTypes
{
    /**
     * The underlying morph types.
     *
     * @var array<class-string<Model>>
     */
    public array $items = [];

    public bool $all = false;

    /**
     * Creates a new morph types instance.
     *
     * @param  class-string<Model>|array<class-string<Model>>|true  $items
     */
    public function __construct(array|string|bool $items)
    {
        if ($items === true) {
            $this->all = true;
        } else {
            $this->items = (array) $items;
        }
    }
}
