<?php

namespace spec\Cmp\CircuitBreaker\Exception;

use PhpSpec\ObjectBehavior;

class ServiceAlreadyTrackedExceptionSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Cmp\CircuitBreaker\Exception\ServiceAlreadyTrackedException');
    }
}
