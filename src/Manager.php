<?php

/**
 * 本文件属于 hughcube/laravel-lark。
 *
 * (c) hugh.li <hugh.li@foxmail.com>
 *
 * 完整版权与许可信息见随附的 MIT 协议。
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
     * 已创建的机器人实例缓存。
     *
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
     * 读取本包命名空间下的配置项。
     *
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
     * 以 defaults 为基底、指定配置覆盖其上，合并后返回。
     *
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
     * 按名称获取一个机器人实例。
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
