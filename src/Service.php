<?php

namespace Cmp\CircuitBreaker;

class Service
{
    protected $name;
    protected $maxFailures;
    protected $retryTimeout;

    public function __construct( $name, $maxFailures = 20, $retryTimeout = 60)
    {
        $this->name         = $name;
        $this->maxFailures  = $maxFailures;
        $this->retryTimeout = $retryTimeout;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getMaxFailures()
    {
        return $this->maxFailures;
    }

    public function getRetryTimeout()
    {
        return $this->retryTimeout;
    }
}
