<?php

namespace Illusion\Container;

use Closure;
use ArrayAccess;
use ReflectionClass;

class Container implements ArrayAccess
{
    private $bindings = [];

    /**
     * Instanciates a new Container.
     * @param array $args
     */
    public function __construct($args = [])
    {
        $this->bindings = $args;
    }

    /**
     * Registers a binding in the Container.
     * @param  string $key
     * @param  mixed  $value
     * @return void
     */
    public function register($key, $value)
    {
        $this->offsetSet($key, $value);
    }

    /**
     * Resolves a binding in the Container.
     * @param  string $key
     * @param  array  $args
     * @return mixed
     */
    public function resolve($key, $args = [])
    {
        $class = $this->get($key);

        if ($class === null) {
            $class = $key;
        }

        if (is_string($class)) {
            return $this->direct($class);
        }

        if ($class instanceof Closure) {
            return $this->closure($class);
        }
    }

    /**
     * Resolves an instance based on the class name.
     *
     * @param string $class
     * @param array  $args
     * @return mixed
     */
    protected function direct($class)
    {
        $args = [];

        $reflect = new ReflectionClass($class);

        if (! $reflect->isInstantiable()) {
            throw new Exception(sprintf('"%s" is not instantiable.'));
        }

        if (! is_null($reflect->getConstructor())) {
            $dependencies = $reflect->getConstructor()->getParameters();

            foreach ($dependencies as $dependency) {
                if ($dependency->isArray() || $dependency->isOptional()) {
                    continue;
                }

                $class = $dependency->getClass();

                if (is_null($class)) {
                    continue;
                }

                array_unshift($args, $this->resolve($class->name));
            }
        }

        return $reflect->newInstanceArgs($args);
    }

    /**
     * Resolves an instance based on a passed closure.
     *
     * @param object $class
     * @param array  $args
     * @return mixed
     */
    protected function closure($callback, $args = [])
    {
        return call_user_func_array($callback, array($this));
    }

    /**
     * Gets a given binding value.
     * @param  string $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->offsetGet($key);
    }

    /**
     * Checks if the Container has a given binding.
     * @param  string $key
     * @return boolean
     */
    public function has($key)
    {
        return isset($this->bindings[$key]);
    }

    /**
     * Removes an given binding from the Container it it exists.
     * @param  string $key
     * @return void
     */
    public function delete($key)
    {
        $this->offsetUnset($key);
    }

    /**
     * Gets a given binding value.
     * @param  string $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        if (! $this->has($key)) {
            return null;
        }

        return $this->bindings[$key];
    }

    /**
     * Registers a binding in the Container.
     * @param  string $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        if (is_null($key)) {
            $this->bindings[] = $value;
        } else {
            $this->bindings[$key] = $value;
        }
    }

    /**
     * Checks if the Container has a given binding.
     * @param  string $key
     * @return boolean
     */
    public function offsetExists($key)
    {
        return isset($this->bindings[$key]);
    }

    /**
     * Removes an given binding from the Container it it exists.
     * @param  string $key
     * @return void
     */
    public function offsetUnset($key)
    {
        if ($this->has($key)) {
            unset($this->bindings[$key]);
        }
    }
}
