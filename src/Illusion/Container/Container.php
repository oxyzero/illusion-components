<?php

namespace Illusion\Container;

use Closure;
use ArrayAccess;
use ReflectionClass;

class Container implements ArrayAccess
{
    /**
     * The registered bindings.
     * @var array
     */
    protected $bindings = [];

    /**
     * The registered shared instances.
     * @var array
     */
    protected $instances = [];

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
    public function register($key, $value = null, $shared = false)
    {

        // If the value is a string
        // then we're going to interpret it as a namespace
        // that points into a class.
        if (is_string($value) && ! $this->hasMissingBackSlash($value)) {
            $value = '\\' . $value;
        }

        // If the value is null, then we are going to
        // look for a class name that has the same name
        // as the key.
        if (is_null($value)) {
            $value = ucfirst($key);
        }

        $this->bindings[$key] = compact('value', 'shared');
    }

    /**
     * Resolves a binding in the Container.
     * @param  string $key
     * @param  array  $args
     * @return mixed
     */
    public function resolve($key, $args = [])
    {
        $object = $this->getValue($key);

        if (isset($this->instances[$key]))
        {
            return $this->instances[$key];
        }

        if ($object === null) {
            $object = $key;
        }

        if (is_string($object)){
            $object = $this->resolveClass($object);
        } else {
            if ($object instanceof Closure) {
                $object = $this->resolveClosure($object);
            }
        }

        return $this->instances[$key] = $object;
    }

    /**
     * Resolves an instance based on the class name.
     *
     * @param string $class
     * @param array  $args
     * @return mixed
     */
    protected function resolveClass($class)
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
    protected function resolveClosure($callback, $args = [])
    {
        return call_user_func_array($callback, array($this));
    }

    /**
     * Gets the value of a given binding.
     * @param  string $key
     * @return mixed
     */
    protected function getValue($key)
    {
        return $this->has($key) ? $this->bindings[$key]['value'] : null;
    }

    /**
     * Checks if the given binding is a shared instance.
     * @param  string $key
     * @return boolean
     */
    protected function isShared($key)
    {
        return $this->has($key) && $this->bindings[$key]['shared'] === true;
    }

    /**
     * Checks if a binding class has a missing backslash.
     * @param  string $key
     * @return boolean
     */
    protected function hasMissingBackslash($key)
    {
        return substr($key, 0, 1) === '\\';
    }

    /**
     * Gets a given binding value.
     * @param  string $key
     * @return mixed
     */
    public function get($key)
    {
        if (! $this->has($key)) {
            return null;
        }

        return $this->bindings[$key];
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
        if ($this->has($key)) {
            unset($this->bindings[$key]);
        }
    }

    /**
     * Gets a given binding value.
     * @param  string $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->resolve($key);
    }

    /**
     * Registers a binding in the Container.
     * @param  string $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->register($key, $value, false);
    }

    /**
     * Checks if the Container has a given binding.
     * @param  string $key
     * @return boolean
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * Removes an given binding from the Container it it exists.
     * @param  string $key
     * @return void
     */
    public function offsetUnset($key)
    {
        $this->delete($key);
    }
}
