<?php

namespace Cmp\CircuitBreaker;

/**
 * Class TimeFactory
 *
 * @package Cmp\CircuitBreaker
 */
class TimeFactory
{
    /**
     * @return int
     */
    public function time()
    {
        return time();
    }
}
