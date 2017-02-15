<?php

namespace Cmp\CircuitBreaker;

interface CircuitBreakerInterface
{
    public function trackService(Service $service);
    public function isAvailable($serviceName);
    public function reportFailure($serviceName);
    public function reportSuccess($serviceName);
}
