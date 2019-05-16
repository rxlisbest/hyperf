<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf-cloud/hyperf/blob/master/LICENSE
 */

namespace Hyperf\Cache;

use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Cache\Annotation\CacheEvict;
use Hyperf\Cache\Annotation\CachePut;
use Hyperf\Cache\Exception\CacheException;
use Hyperf\Cache\Helper\StringHelper;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Di\Annotation\AbstractAnnotation;
use Hyperf\Di\Annotation\AnnotationCollector;

class AnnotationManager
{
    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

    public function __construct(ConfigInterface $config, StdoutLoggerInterface $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    public function getCacheableValue(string $className, string $method, array $arguments)
    {
        /** @var Cacheable $annotation */
        $annotation = $this->getAnnotation(Cacheable::class, $className, $method);

        $key = $this->getFormatedKey($annotation->prefix, $arguments, $annotation->value);
        $group = $annotation->group;
        $ttl = $annotation->ttl ?? $this->config->get("cache.{$group}.ttl", 3600);

        return [$key, $ttl, $group];
    }

    public function getCacheEvictValue(string $className, string $method, array $arguments)
    {
        /** @var CacheEvict $annotation */
        $annotation = $this->getAnnotation(CacheEvict::class, $className, $method);

        $prefix = $annotation->prefix;
        $all = $annotation->all;
        $group = $annotation->group;
        if (! $all) {
            $key = $this->getFormatedKey($prefix, $arguments, $annotation->value);
        } else {
            $key = $prefix . ':';
        }

        return [$key, $all, $group];
    }

    public function getCachePutValue(string $className, string $method, array $arguments)
    {
        /** @var CachePut $annotation */
        $annotation = $this->getAnnotation(CachePut::class, $className, $method);

        $key = $this->getFormatedKey($annotation->prefix, $arguments, $annotation->value);
        $group = $annotation->group;
        $ttl = $annotation->ttl ?? $this->config->get("cache.{$group}.ttl", 3600);

        return [$key, $ttl, $group];
    }

    protected function getAnnotation(string $annotation, string $className, string $method): AbstractAnnotation
    {
        $collector = AnnotationCollector::get($className);
        $result = $collector['_m'][$method][$annotation] ?? null;
        if (! $result instanceof $annotation) {
            throw new CacheException(sprintf('Annotation %s in %s:%s not exist.', $annotation, $className, $method));
        }

        return $result;
    }

    protected function getFormatedKey(string $prefix, array $arguments, ?string $value = null)
    {
        $key = StringHelper::format($prefix, $arguments, $value);

        if (strlen($key) > 64) {
            $this->logger->warning('The cache key length is too long. The key is ' . $key);
        }

        return $key;
    }
}