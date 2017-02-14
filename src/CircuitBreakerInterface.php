<?php
namespace Cmp\CircuitBreaker;

interface CircuitBreakerInterface {
  public function isAvailable($serviceName);
  public function reportFailure($serviceName);
  public function reportSuccess($serviceName);
}
