<?php

namespace Reedware\LaravelRelationJoins\Concerns;

use ReflectionClass;
use ReflectionProperty;

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
        return new static;
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

        	$this->{$property->getName()} = $property->getValue($parent);

        }

        return $this;
    }

    /**
     * Returns the value of the specified property from the given parent.
     *
     * @param  mixed                       $parent
     * @param  \ReflectionProperty|string  $property
     * @return mixed
     */
    public static function getParentPropertyValue($parent, $property)
    {
        if(is_string($property)) {
            $property = new ReflectionProperty($parent, $property);
        }

        $property->setAccessible(true);

        return $property->getValue($parent);
    }

    /**
     * Assigns the acquired properties from this class back onto the parent.
     *
     * @param  mixed  $parent
     * @return void
     */
    public function assignAcquiredProperties($parent)
    {
        $self = new ReflectionClass($this);

        foreach ((new ReflectionClass($parent))->getProperties() as $parentProperty) {

            if(!$self->hasProperty($parentProperty->getName())) {
                continue;
            }

            $selfProperty = $self->getProperty($parentProperty->getName());

            if($parentProperty->isStatic()) {
                continue;
            }

            $parentProperty->setAccessible(true);
            $selfProperty->setAccessible(true);

            $parentProperty->setValue($parent, $selfProperty->getValue($this));

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

        $instance->inheritProperties($parent);

        $result = $instance->{$method}(...$arguments);

        $instance->assignAcquiredProperties($parent);

        return $result === $instance ? $parent : $result;
    }
}
