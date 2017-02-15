<?php

namespace spec\Cmp\CircuitBreaker;

use Cmp\CircuitBreaker\Service;
use PhpSpec\ObjectBehavior;

class ServiceSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->beConstructedWith('test');
        $this->shouldHaveType(Service::class);
    }

    function it_should_not_allow_empty_service_names()
    {
        $this->beConstructedWith('');
        $this->shouldThrow('\InvalidArgumentException')->duringInstantiation();
    }
}
