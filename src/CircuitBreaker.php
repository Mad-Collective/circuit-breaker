<?php
namespace Cmp\CircuitBreaker;

use Psr\SimpleCache\CacheInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class CircuitBreaker
{
  protected $cache;
  protected $maxFailures;
  protected $retryTimeout;
  protected $cachePrefix;
  protected $ttl;
  protected $logger;

  public function __construct(
      CacheInterface $cache,
      LoggerInterface $logger = null,
      $maxFailures  = 20,
      $retryTimeout = 60,
      $cachePrefix  = 'cb',
      $ttl          = 3360
  ){
    $this->cache        = $cache;
    $this->cachePrefix  = $cachePrefix;
    $this->maxFailures  = $maxFailures;
    $this->retryTimeout = $retryTimeout;
    $this->ttl          = $ttl;
    $this->logger       = $logger instanceof LoggerInterface ? $logger : new NullLogger();
  }

  protected getCacheKey( $serviceName,  $field )
  {
    return $this->cachePrefix . '-' . $serviceName . '-' . $field;
  }

  protected function getFailures( $serviceName )
  {
    return (int) $this->cache->get( $this->getCacheKey($serviceName, 'failures') );
  }

  protected function getLastTest( $serviceName )
  {
    return (int) $this->cache->get( $this->getCacheKey($serviceName, 'lastTest') );
  }

  protected function setFailures( $serviceName, $newValue )
  {
    $this->cache->set( $this->getCacheKey($serviceName, 'failures'), $newValue, $this->ttl );
    $this->cache->set( $this->getCacheKey($serviceName, 'lastTest'), time(), $this->ttl );
  }

  public function isAvailable( $serviceName )
  {
    $failures    = $this->getFailures( $serviceName );
    $maxFailures = $this->maxFailures;

    if ($failures < $maxFailures)
    {
        return true;
    }
    else
    {
      $lastTest     = $this->getLastTest($serviceName);
      $retryTimeout = $this->retryTimeout;

      if ( ($lastTest + $retryTimeout) < time())
      {
          $this->setFailures($serviceName, $failures);
          return true;
      }
      else
      {
          return false;
      }
    }
  }

  public function reportFailure( $serviceName )
  {
    $this->setFailures( $serviceName, $this->getFailures($serviceName) + 1 );
    $this->logger->warning("Service " . $serviceName . " is shaky");
  }

  public function reportSuccess( $serviceName )
  {
    $failures    = $this->getFailures( $serviceName );
    $maxFailures = $this->maxFailures;

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
