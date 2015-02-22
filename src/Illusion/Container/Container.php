<?php

namespace Illusion\Container;

use Closure;
use ArrayAccess;
use ReflectionClass;
use ReflectionMethod;
use InvalidArgumentException;

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
     * The registered protected parameters.
     * @var array
     */
    protected $protected = [];

    /**
     * The registered resolved bindings.
     * @var array
     */
    protected $resolved = [];

    /**
     * The registered binding extensions.
     * @var array
     */
    protected $extensions = [];

    /**
     * Registers a binding in the Container.
     * @param  string $key
     * @param  mixed  $value
     * @return void
     */
    public function register($key, $value = null, $shared = false)
    {
        // If the value is null, then we are going to
        // look for a class name that has the same name
        // as the key.
        if (is_null($value)) {
            $value = ucfirst($key);
        }

        // If the value is a string
        // then we're going to interpret it as a namespace
        // that points into a class.
        $value = $this->handleMissingBackslash($value);

        $this->bindings[$key] = compact('value', 'shared');
    }

    /**
     * Generate a binding that has a shared instance.
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function singleton($key, $value = null)
    {
        $this->register($key, $value, true);
    }

    /**
     * Alias of singleton.
     * Generate a binding that has a shared instance.
     * @param  string $key
     * @param  mixed $value
     * @return void
     * @see singleton()
     */
    public function share($key, $value = null)
    {
        $this->singleton($key, $value);
    }

    /**
     * Resolves a binding in the Container.
     * @param  string $key
     * @param  array  $args
     * @return mixed
     */
    public function resolve($key, $args = [])
    {
        if (isset($this->instances[$key])) {
            return $this->instances[$key];
        }

        $object = $this->getValue($key);

        if (is_null($object)) {
            $object = $key;
        }

        // If the object is not buildable it means
        // that it is already an instance, so we have to
        // get it's class name and provide a new instance.
        if (! $this->isBuildable($object)) {
            $object = $this->getClassName($object);
        }

        $object = $this->build($object, $args);

        // Apply all of the extensions into the object.
        foreach ($this->getExtensions($key) as $extension) {
            $object = $this->resolveClosure($extension, [ $object ]);
        }

        if ($this->isShared($key)) {
            $this->instances[$key] = $object;
        }

        $this->resolved[$key] = true;

        return $object;
    }

    /**
     * Get the extensions of a given binding.
     * @param  string $key
     * @return array
     */
    protected function getExtensions($key)
    {
        return isset($this->extensions[$key]) ? $this->extensions[$key] : [];
    }

    /**
     * Returns the class name of an instance.
     * @param  object $class
     * @return string
     */
    protected function getClassName($class)
    {
        $class = new ReflectionClass($class);

        return $class->getName();
    }

    /**
     * Checks if the given value is buildable.
     * @param  mixed $key
     * @return boolean
     */
    protected function isBuildable($value)
    {
        return is_string($value) || $this->isClosure($value);
    }

    /**
     * Builds an instance.
     * @param  mixed $key
     * @param  array $args
     * @return mixed
     */
    protected function build($key, $args = [])
    {
        if ($this->isClosure($key)) {
            return $this->resolveClosure($key, $args);
        }

        return $this->resolveClass($key, $args);
    }

    /**
     * Registers an already defined instance as
     * a shared instance in the container.
     * @param  string $key
     * @param  object $instance
     * @param  boolean $shared
     * @return void
     */
    public function instance($key, $instance, $shared = true)
    {
        $this->bindings[$key] = [ 'value' => $instance, 'shared' => $shared ];

        if ($shared) {
            $this->instances[$key] = $instance;
        }
    }

    /**
     * Removes an instance from the container to allow
     * to instantiate it again.
     * @param  string $key
     * @return void
     */
    public function deleteInstance($key)
    {
        unset($this->instances[$key]);
    }

    /**
     * Removes all instances from the container to allow
     * to instantiate them again.
     * @return void
     */
    public function deleteInstances()
    {
        $this->instances = [];
    }

    /**
     * Releases all of the bindings and instances.
     * @return void
     */
    public function flush()
    {
        $this->bindings   = [];
        $this->resolved   = [];
        $this->instances  = [];
        $this->extensions = [];
        $this->protected  = [];
    }

    /**
     * Registers a protected parameter in the container.
     * @param  string $key
     * @param  mixed $closure
     * @return void
     */
    public function protect($key, $closure)
    {
        $this->protected[$key] = $closure;
    }

    /**
     * Returns a protected parameter if it exists.
     * @param  string $key
     * @param  array $array
     * @return mixed
     */
    public function getProtected($key, $args = [])
    {
        $object = $this->protected[$key];

        if (! isset($object)) {
            return null;
        }

        if ($this->isClosure($object)) {
            $object = $this->resolveClosure($object, $args);
        }

        return $object;
    }

    /**
     * Extend a binding.
     * @param  string $key
     * @param  Closure $closure [description]
     * @return mixed
     */
    public function extend($key, $closure)
    {
        if (! $this->has($key)) {
            return new InvalidArgumentException(
                sprintf('The binding "%s" isn\'t registered in the container.', $key)
            );
        }

        if (! $this->isClosure($closure)) {
            return new InvalidArgumentException(
                'The extension definition is not a Closure.'
            );
        }

        if ($this->isShared($key)) {
            $object = $this->instances[$key];
        } else {
            $object = $this->resolve($key);
        }

        $this->extensions[$key][] = $closure;

        $extension = $this->resolveClosure($closure, [ $object ]);

        if ($this->isShared($key)) {
            $this->instances[$key] = $extension;
        }

        return $extension;
    }

    /**
     * Resolves an instance based on the class name.
     *
     * @param string $class
     * @param array  $args
     * @return mixed
     */
    protected function resolveClass($class, $args = [])
    {
        $class = new ReflectionClass($class);

        $dependencies = $this->getClassDependencies($class);

        foreach ($dependencies as $dependency) {
            $args[] = $this->resolve($dependency);
        }

        return $class->newInstanceArgs($args);
    }

    /**
     * Builds the dependencies needed to resolve a class.
     * @param  ReflectionClass $class
     * @return array
     */
    protected function getClassDependencies(ReflectionClass $class)
    {
        if (! $class->isInstantiable()) {
            throw new InvalidArgumentException(sprintf('"%s" is not instantiable.', $class));
        }

        $constructor = $class->getConstructor();

        // If there is no constructor, then we don't need to inject dependencies.
        if (is_null($constructor)) {
            return [];
        }

        $classDependencies = [];
        $dependencies = $constructor->getParameters();

        foreach ($dependencies as $dependency) {
            if ($dependency->isArray() || $dependency->isOptional()) {
                continue;
            }

            $classDependency = $dependency->getClass();

            if (! is_null($class)) {
                $classDependencies[] = $classDependency->name;
            }
        }

        return $classDependencies;
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
        // Allow the container to be
        // within the last position of the arguments.
        $args[] = $this;

        return call_user_func_array($callback, $args);
    }

    /**
     * Calls method and instantiates all of its dependencies.
     * @param  string $key
     * @return mixed
     */
    public function method($key)
    {
        list($class, $method) = explode('@', $key);

        $class = $this->resolveClass($class);

        $method = $this->resolveMethod($class, $method);

        return $method;
    }

    /**
     * Resolves a method.
     * @param  object $class
     * @param  string $method
     * @return mixed
     */
    protected function resolveMethod($class, $method)
    {
        $method = new ReflectionMethod($class, $method);

        $parameters = $method->getParameters();
        $methodDependencies = [];

        foreach ($parameters as $parameter) {
            if ($parameter->isArray() || $parameter->isOptional()) {
                continue;
            }

            $dependency = $parameter->getClass();

            if (! is_null($dependency)) {
                $methodDependencies[] = $this->resolve($dependency->name);
            }
        }

        return $method->invokeArgs($class, $methodDependencies);
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
     * Checks if a binding is resolved.
     * @param  string $key
     * @return boolean
     */
    protected function isResolved($key)
    {
        return isset($this->resolved[$key]) ? $this->resolved[$key] : false;
    }

    /**
     * Checks if the given binding is a shared instance.
     * @param  string $key
     * @return boolean
     */
    protected function isShared($key)
    {
        return isset($this->bindings[$key]['shared']) ? $this->bindings[$key]['shared'] : false;
    }

    /**
     * Fixes the missing backslash on class names.
     * @param  string $key
     * @return mixed
     */
    protected function handleMissingBackslash($key)
    {
        if (is_string($key) && ! $this->hasMissingBackslash($key)) {
            $key = '\\' . $key;
        }

        return $key;
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
     * Checks if an object is an instance of a Closure.
     * @param  object $object
     * @return boolean
     */
    protected function isClosure($object)
    {
        return $object instanceof Closure ? true : false;
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
        unset($this->bindings[$key], $this->instances[$key], $this->resolved[$key]);
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

    /**
     * Dynamically access the binding value.
     * @param  string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this[$key];
    }

    /**
     * Dynamically set the binding value.
     * @param string $key
     * @param mixed $value
     */
    public function __set($key, $value = null)
    {
        $this[$key] = $value;
    }

    /**
     * Dynamically checks if a binding exists.
     * @param  string $key
     * @return boolean
     */
    public function __isset($key)
    {
        return isset($this[$key]);
    }

    /**
     * Dynamically delete a binding.
     * @param string $key
     */
    public function __unset($key)
    {
        unset($this[$key]);
    }

    /**
     * Returns all keys within the container.
     * @return array
     */
    public function keys()
    {
        return array(
            'bindings'   => $this->bindings,
            'resolved'   => $this->resolved,
            'instances'  => $this->instances,
            'extensions' => $this->extensions,
            'protected'  => $this->protected,
        );
    }
}
