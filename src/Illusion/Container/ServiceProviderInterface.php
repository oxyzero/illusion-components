<?php

namespace Illusion\Container;

interface ServiceProviderInterface
{
    /**
     * Register an service in the container.
     * @param  Illusion\Container $container
     * @return void
     */
    public function register(Illusion\Container $container);
}
