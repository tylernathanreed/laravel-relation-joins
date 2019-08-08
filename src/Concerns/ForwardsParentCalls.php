<?php

namespace Reedware\LaravelRelationJoins\Concerns;

use ReflectionClass;

trait ForwardsParentCalls
{
    /**
     * Creates a new instance of this class using a parent instance.
     *
     * @param  mixed  $parent
     * @return static
     */
    public static function newFromParent($parent)
    {
        return (new static)->inheritProperties($parent);
    }

    /**
     * Inherits the properties from the specified parent class.
     *
     * @param  mixed  $parent
     * @return void
     */
    public function inheritProperties($parent)
    {
        foreach ((new ReflectionClass($parent))->getProperties() as $property) {

            if($property->isStatic()) {
                continue;
            }

            $property->setAccessible(true);

        	$this->{$property->getName()} = $property->getValue($this);

        }

        return $this;
    }

    /**
     * Handles an incoming call from the specified parent instance.
     *
     * @param  mixed   $parent
     * @param  string  $method
     * @param  array   $arguments
     * @return mixed
     */
    public static function callFromParent($parent, $method, $arguments = [])
    {
        $instance = static::newFromParent($parent);

        $result = $instance->{$method}(...$arguments);

        return $result === $instance ? $parent : $result;
    }
}