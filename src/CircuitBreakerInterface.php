<?php

namespace Cmp\CircuitBreaker;

/**
 * Interface CircuitBreakerInterface
 *
 * @package Cmp\CircuitBreaker
 */
interface CircuitBreakerInterface
{
    /**
     * @param Service $service Service to keep track of
     */
    public function trackService(Service $service);

    /**
     * @param string $serviceName Name of the service
     *
     * @return bool
     */
    public function isAvailable($serviceName);

    /**
     * @param string $serviceName Name of the service
     */
    public function reportFailure($serviceName);

    /**
     * @param string $serviceName Name of the service
     */
    public function reportSuccess($serviceName);
}
