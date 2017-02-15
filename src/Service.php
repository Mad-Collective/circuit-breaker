<?php

namespace Cmp\CircuitBreaker;

/**
 * Class Service
 *
 * @package Cmp\CircuitBreaker
 */
class Service
{
    protected $name;

    protected $maxFailures;

    protected $retryTimeout;

    /**
     * Service constructor.
     *
     * @param string $name         Name of the service
     * @param int    $maxFailures  Maximum numbers of allowed failures
     * @param int    $retryTimeout Timeout to retry the service
     */
    public function __construct($name, $maxFailures = 20, $retryTimeout = 60)
    {
        $this->setName($name);
        $this->maxFailures  = $maxFailures;
        $this->retryTimeout = $retryTimeout;
    }

    /**
     * @param string $name Name of the service
     */
    protected function setName($name)
    {
        if ($name == '') {
            throw new \InvalidArgumentException('Service name can\'t be empty');
        }

        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getMaxFailures()
    {
        return $this->maxFailures;
    }

    /**
     * @return int
     */
    public function getRetryTimeout()
    {
        return $this->retryTimeout;
    }
}
