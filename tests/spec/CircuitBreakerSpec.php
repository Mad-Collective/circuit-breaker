<?php

namespace spec\Cmp\CircuitBreaker;

use Cmp\CircuitBreaker\CircuitBreaker;
use Cmp\CircuitBreaker\Service;
use Cmp\CircuitBreaker\TimeFactory;
use PhpSpec\ObjectBehavior;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;


class CircuitBreakerSpec extends ObjectBehavior
{
    function let(CacheInterface $cache, TimeFactory $timeFactory, LoggerInterface $logger)
    {
        $this->beConstructedWith($cache, $logger, $timeFactory);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(CircuitBreaker::class);
    }

    function it_should_not_allow_track_the_same_service_more_than_once()
    {
        $service = new Service('test');
        $this->trackService($service);
        $this->shouldThrow('\Cmp\CircuitBreaker\Exception\ServiceAlreadyTrackedException')->during('trackService', [$service]);
    }

    function it_should_throw_an_exception_if_the_service_is_not_tracked()
    {
        $this->shouldThrow('\Cmp\CircuitBreaker\Exception\ServiceNotTrackedException')->during('isAvailable', ['test']);
    }

    function it_should_say_service_is_available_when_no_attempts(CacheInterface $cache)
    {
        $cache->get('cb-test-failures')->willReturn(0);
        $service = new Service('test');
        $this->trackService($service);
        $this->isAvailable('test')->shouldReturn(true);
    }

    function it_should_say_a_service_is_available_when_reported_down_some_times(CacheInterface $cache)
    {
        $cache->get('cb-test-failures')->willReturn(18);
        $service = new Service('test', 20, 60);
        $this->trackService($service);
        $this->isAvailable('test')->shouldReturn(true);
    }

    function it_should_say_a_service_is_not_available_when_reported_enough_times(CacheInterface $cache, TimeFactory $timeFactory)
    {
        $cache->get('cb-test-failures')->willReturn(20);
        $cache->get('cb-test-lastTest')->willReturn(50);
        $timeFactory->time()->willReturn(100);
        $service = new Service('test', 20, 60);
        $this->trackService($service);
        $this->isAvailable('test')->shouldReturn(false);
    }
}
