<?php

namespace spec\Cmp\CircuitBreaker;

use Cmp\CircuitBreaker\TimeFactory;
use PhpSpec\ObjectBehavior;

class TimeFactorySpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(TimeFactory::class);
    }

    function it_should_get_a_valid_timestamp()
    {
        $this->time()->shouldBeInteger();
    }
}
