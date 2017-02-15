<?php

namespace Cmp\CircuitBreaker;

use Cmp\CircuitBreaker\Exception\ServiceAlreadyTrackedException;
use Cmp\CircuitBreaker\Exception\ServiceNotTrackedException;
use Psr\SimpleCache\CacheInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class CircuitBreaker
 * @package Cmp\CircuitBreaker
 */
class CircuitBreaker
{
    protected $cache;
    protected $logger;
    protected $services = array();
    protected $cachePrefix = 'cb';
    protected $ttl = 3360;

    /**
     * CircuitBreaker constructor.
     * @param CacheInterface $cache
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        CacheInterface $cache,
        LoggerInterface $logger = null
    ){
        $this->cache  = $cache;
        $this->logger = $logger instanceof LoggerInterface ? $logger : new NullLogger();
    }

    /**
     * @param $serviceName
     * @param $field
     * @return string
     */
    protected function getCacheKey($serviceName, $field )
    {
        return $this->cachePrefix . '-' . $serviceName . '-' . $field;
    }

    /**
     * @param $serviceName
     * @return mixed|null
     */
    protected function getService( $serviceName )
    {
        if (array_key_exists($serviceName, $this->services))
        {
            return $this->services[$serviceName];
        }
        return null;
    }

    /**
     * @param $serviceName
     * @return int
     */
    protected function getFailures( $serviceName )
    {
        return (int) $this->cache->get( $this->getCacheKey($serviceName, 'failures') );
    }

    /**
     * @param $serviceName
     * @return int
     */
    protected function getLastTest( $serviceName )
    {
        return (int) $this->cache->get( $this->getCacheKey($serviceName, 'lastTest') );
    }

    /**
     * @param $serviceName
     * @param $newValue
     */
    protected function setFailures( $serviceName, $newValue )
    {
        $this->cache->set( $this->getCacheKey($serviceName, 'failures'), $newValue, $this->ttl );
        $this->cache->set( $this->getCacheKey($serviceName, 'lastTest'), time(),    $this->ttl );
    }

    /**
     * @param Service $service
     * @throws ServiceAlreadyTrackedException
     */
    public function trackService( Service $service )
    {
        if ($this->getService($service) !== null)
        {
            throw new ServiceAlreadyTrackedException( $service->getName() );
        }
        $this->services[$service->getName()] = $service;
    }

    /**
     * @param $serviceName
     * @return bool
     * @throws ServiceNotTrackedException
     */
    public function isAvailable( $serviceName )
    {
        $service = $this->getService( $serviceName );
        if ( $service == null )
        {
            throw new ServiceNotTrackedException( $service->getName() );
        }

        $failures    = $this->getFailures( $serviceName );
        $maxFailures = $service->getMaxFailures();

        // Service is available
        if ($failures < $maxFailures)
        {
            return true;
        }

        $lastTest     = $this->getLastTest( $serviceName );
        $retryTimeout = $service->getRetryTimeout();

        // Try the service one more time
        if ( ($lastTest + $retryTimeout) < time())
        {
            $this->setFailures($serviceName, $failures);
            return true;
        }

        // Service down
        return false;
    }

    /**
     * @param $serviceName
     * @throws ServiceNotTrackedException
     */
    public function reportFailure($serviceName )
    {
        // Check if we're tracking the service
        if ( $this->getService( $serviceName ) == null )
        {
            throw new ServiceNotTrackedException( $serviceName );
        }

        $this->setFailures( $serviceName, $this->getFailures($serviceName) + 1 );
        $this->logger->warning('Service ' . $serviceName . ' is shaky');
    }

    /**
     * @param $serviceName
     * @throws ServiceNotTrackedException
     */
    public function reportSuccess($serviceName )
    {
        // Check that we're tracking the service
        $service = $this->getService( $serviceName );
        if ( $service == null )
        {
            throw new ServiceNotTrackedException( $service->getName() );
        }

        $failures    = $this->getFailures( $serviceName );
        $maxFailures = $service->getMaxFailures();

        if ( $failures > $maxFailures )
        {
            $this->setFailures( $serviceName, $maxFailures - 1 );
            $this->logger->alert('Service ' . $serviceName . ' is down');
        }
        elseif( $failures > 0 )
        {
            $this->setFailures( $serviceName, $failures - 1 );
        }
    }
}
