<?php

namespace spec\Cmp\CircuitBreaker\Exception;

use PhpSpec\ObjectBehavior;

class ServiceNotTrackedExceptionSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Cmp\CircuitBreaker\Exception\ServiceNotTrackedException');
    }
}
