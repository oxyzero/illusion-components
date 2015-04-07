<?php

namespace Illusion\Support\Mist;

abstract class Mist
{
    /**
     * The container instance.
     *
     * @var \Illusion\Container\Container
     */
    protected static $container;

    /**
     * The resolved instances.
     *
     * @var array
     */
    protected static $resolvedInstances;

    /**
     * Sets a container to resolve instances.
     *
     * @param \Illusion\Container\Container $container
     */
    public static function setContainer($container)
    {
        static::$container = $container;
    }

    /**
     * Gets the container instance.
     *
     * @return \Illusion\Container\Container
     */
    public static function getContainer()
    {
        return static::$container;
    }

    /**
     * Clear a resolved mist instance.
     *
     * @param  string $name
     *
     * @return void
     */
    public static function clearResolvedInstance($name)
    {
        unset(static::$resolvedInstances[$name]);
    }

    /**
     * Clears all of the resolved mist instances.
     *
     * @return void
     */
    public static function clearResolvedInstances()
    {
        static::$resolvedInstances = [];
    }

    /**
     * Define a mist instance.
     *
     * @return mixed
     */
    protected static function mist()
    {
        return null;
    }

    /**
     * Resolves a mist instance.
     *
     * @param  mixed $instance
     *
     * @return mixed
     */
    protected static function resolveMistInstance($instance)
    {
        if (is_object($instance)) {
            return $instance;
        }

        if (isset(static::$resolvedInstances[$instance])) {
            return static::$resolvedInstances[$instance];
        }

        return static::$resolvedInstances[$instance] = static::$container[$instance];
    }

    /**
     * Gets the root instance from the mist.
     *
     * @return mixed
     */
    public static function getMistRoot()
    {
        return static::resolveMistInstance(static::mist());
    }

    /**
     * Handle dynamic calls to mist instances.
     *
     * @param  string  $method
     * @param  array   $args
     * @return mixed
     */
    public static function __callStatic($method, $args)
    {
        return call_user_func_array([static::getMistRoot(), $method], $args);
    }
}
