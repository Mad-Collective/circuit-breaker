<?php

namespace Cmp\CircuitBreaker;

use Cmp\CircuitBreaker\Exception\ServiceAlreadyTrackedException;
use Cmp\CircuitBreaker\Exception\ServiceNotTrackedException;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface;
use Psr\Log\LoggerInterface;

/**
 * Class CircuitBreaker
 *
 * @package Cmp\CircuitBreaker
 */
class CircuitBreaker
{
    const DEFAULT_TLL = 3360;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Service[]
     */
    protected $services    = [];

    /**
     * @var string
     */
    protected $cachePrefix = 'cb';

    /**
     * @var int
     */
    protected $ttl;

    /**
     * CircuitBreaker constructor.
     *
     * @param CacheInterface       $cache       Cache to store the failures and times
     * @param LoggerInterface|null $logger      To log stuff
     * @param int                  $ttl         Expiration time of failures and latest keys
     */
    public function __construct(
        CacheInterface $cache,
        LoggerInterface $logger,
        $ttl = self::DEFAULT_TLL
    ) {
        $this->cache       = $cache;
        $this->logger      = $logger instanceof LoggerInterface ? $logger : new NullLogger();
        $this->ttl         = (int) $ttl;
    }

    /**
     * @param string $serviceName Name of the service
     * @param string $field       Field you want to fetch
     *
     * @return string
     */
    protected function getCacheKey($serviceName, $field)
    {
        return $this->cachePrefix.'-'.$serviceName.'-'.$field;
    }

    /**
     * @param $serviceName
     * @return Service|null
     */
    protected function getService($serviceName)
    {
        if (array_key_exists($serviceName, $this->services)) {
            return $this->services[$serviceName];
        }
        return null;
    }

    /**
     * @param string $serviceName Name of the service
     *
     * @return int
     */
    protected function getFailures($serviceName)
    {
        return (int) $this->cache->get($this->getCacheKey($serviceName, 'failures'));
    }

    /**
     * @param string $serviceName Name of the service
     *
     * @return int
     */
    protected function getLastTest($serviceName)
    {
        return (int) $this->cache->get($this->getCacheKey($serviceName, 'lastTest'));
    }

    /**
     * @param string $serviceName Name of the service
     * @param int    $newValue    New value to store
     */
    protected function setFailures($serviceName, $newValue)
    {
        $this->cache->set($this->getCacheKey($serviceName, 'failures'), $newValue, $this->ttl);
        $this->cache->set($this->getCacheKey($serviceName, 'lastTest'), time(), $this->ttl);
    }

    /**
     * @param Service $service Service to track
     *
     * @throws ServiceAlreadyTrackedException
     */
    public function trackService(Service $service)
    {
        if ($this->getService($service->getName()) !== null) {
            throw new ServiceAlreadyTrackedException($service->getName());
        }
        $this->services[$service->getName()] = $service;
    }

    /**
     * @param string $serviceName Name of the service
     *
     * @return bool
     * @throws ServiceNotTrackedException
     */
    public function isAvailable($serviceName)
    {
        $service = $this->getService($serviceName);
        if ($service == null) {
            throw new ServiceNotTrackedException($serviceName);
        }

        $failures    = $this->getFailures($serviceName);
        $maxFailures = $service->getMaxFailures();

        if ($failures < $maxFailures) {
            return true;
        }

        $lastTest     = $this->getLastTest($serviceName);
        $retryTimeout = $service->getRetryTimeout();

        if (($lastTest + $retryTimeout) < time()) {
            $this->setFailures($serviceName, $failures);
            $this->logger->info('Attempting service '.$serviceName.' one more time');

            return true;
        }

        return false;
    }

    /**
     * @param string $serviceName Name of the service
     *
     * @throws ServiceNotTrackedException
     */
    public function reportFailure($serviceName)
    {
        if ($this->getService($serviceName) == null) {
            throw new ServiceNotTrackedException($serviceName);
        }

        $this->setFailures($serviceName, $this->getFailures($serviceName) + 1);
        $this->logger->warning('Service '.$serviceName.' is shaky');
    }

    /**
     * @param string $serviceName            Name of the service
     * @param bool   $decreaseFailureCounter Disable decrease failure counter
     *
     * @throws ServiceNotTrackedException
     */
    public function reportSuccess($serviceName, $decreaseFailureCounter = true)
    {
        $service = $this->getService($serviceName);
        if ($service == null) {
            throw new ServiceNotTrackedException($serviceName);
        }

        if(!$decreaseFailureCounter) {
            return;
        }

        $failures    = $this->getFailures($serviceName);
        $maxFailures = $service->getMaxFailures();

        if ($failures > $maxFailures) {
            $this->setFailures($serviceName, $maxFailures - 1);
            $this->logger->alert('Service '.$serviceName.' is down');
        } elseif ($failures > 0) {
            $this->setFailures($serviceName, $failures - 1);
        }
    }
}
