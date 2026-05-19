<?php

/**
 * This file is part of the hughcube/laravel-lark.
 *
 * (c) hugh.li <hugh.li@foxmail.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace HughCube\Laravel\Lark;

use HughCube\Laravel\Lark\Robot\Client as Robot;
use Illuminate\Config\Repository;
use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container as ContainerContract;

class Manager
{
    /**
     * @var (callable():ContainerContract)|ContainerContract|null
     */
    protected $container;

    /**
     * @var array<string, Robot>
     */
    protected array $robots = [];

    /**
     * @param (callable():ContainerContract)|ContainerContract|null $container
     */
    public function __construct($container = null)
    {
        $this->container = $container;
    }

    protected function getContainer(): ContainerContract
    {
        if (is_callable($this->container)) {
            return call_user_func($this->container);
        }

        if (null === $this->container) {
            return IlluminateContainer::getInstance();
        }

        return $this->container;
    }

    /**
     * @throws BindingResolutionException
     */
    protected function getConfig(string|null $key = null, mixed $default = null): mixed
    {
        /** @var Repository $config */
        $config = $this->getContainer()->make('config');

        $namespace = Lark::getFacadeAccessor();
        $key = empty($key) ? $namespace : "$namespace.$key";

        return $config->get($key, $default);
    }

    /**
     * @param array<mixed>|null $default
     *
     * @throws BindingResolutionException
     *
     * @return array<mixed>
     */
    protected function getConfigWithDefaults(string|null $key = null, ?array $default = []): array
    {
        return array_replace_recursive(
            (array) $this->getConfig('defaults', []),
            (array) $this->getConfig($key, $default)
        );
    }

    /**
     * Get a robot instance by name.
     *
     * @throws BindingResolutionException
     */
    public function robot(string|null $name = null): Robot
    {
        $name ??= 'default';

        if (!isset($this->robots[$name])) {
            $config = $this->getConfigWithDefaults("robots.$name");

            if (empty($config)) {
                throw new \InvalidArgumentException(sprintf('The lark robot [%s] is not defined.', $name));
            }

            $this->robots[$name] = new Robot($config);
        }

        return $this->robots[$name];
    }
}
