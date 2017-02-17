<?php

namespace spec\Cmp\CircuitBreaker;

use Cmp\CircuitBreaker\CircuitBreaker;
use Cmp\CircuitBreaker\Service;
use PhpSpec\ObjectBehavior;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

class CircuitBreakerSpec extends ObjectBehavior
{
    public static $time;

    /**
     * @param Psr\SimpleCache\CacheInterface $cache
     * @param Psr\Log\LoggerInterface        $logger
     */
    function let(CacheInterface $cache, LoggerInterface $logger)
    {
        $this->beConstructedWith($cache, $logger);
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

    function it_should_throw_an_exception_if_the_service_is_not_tracked_in_is_available()
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

    function it_should_say_a_service_is_not_available_when_reported_enough_times(CacheInterface $cache)
    {
        $cache->get('cb-test-failures')->willReturn(20);
        $cache->get('cb-test-lastTest')->willReturn(50);
        CircuitBreakerSpec::$time = 100;
        $service = new Service('test', 20, 60);
        $this->trackService($service);
        $this->isAvailable('test')->shouldReturn(false);
    }

    function it_should_return_say_a_service_is_available_when_exceded_number_of_times_and_retry_time_has_passed(CacheInterface $cache)
    {
        $cache->get('cb-test-failures')->willReturn(20);
        $cache->get('cb-test-lastTest')->willReturn(20);
        $cache->set('cb-test-failures', 20, 3360)->shouldBeCalled();
        $cache->set('cb-test-lastTest', 100, 3360)->shouldBeCalled();
        CircuitBreakerSpec::$time = 100;
        $service = new Service('test', 20, 60);
        $this->trackService($service);
        $this->isAvailable('test')->shouldReturn(true);
    }

    function it_should_throw_exception_in_report_failure_if_the_service_is_not_tracked()
    {
        $this->shouldThrow('\Cmp\CircuitBreaker\Exception\ServiceNotTrackedException')->during('reportFailure', ['test']);
    }

    function it_should_call_the_cache_when_reported_failure(CacheInterface $cache)
    {
        CircuitBreakerSpec::$time = 1000;
        $cache->get('cb-test-failures')->willReturn(20);
        $cache->set('cb-test-failures', 21, 3360)->shouldBeCalled();
        $cache->set('cb-test-lastTest', 1000, 3360)->shouldBeCalled();
        $service = new Service('test', 20, 60);
        $this->trackService($service);
        $this->reportFailure('test');
    }

    function it_should_throw_exception_in_report_success_if_the_service_is_not_tracked()
    {
        $this->shouldThrow('\Cmp\CircuitBreaker\Exception\ServiceNotTrackedException')->during('reportSuccess', ['test']);
    }

    function it_should_substract_a_failure_from_max(CacheInterface $cache)
    {
        CircuitBreakerSpec::$time = 1000;
        $cache->get('cb-test-failures')->willReturn(21);
        $cache->set('cb-test-failures', 19, 3360)->shouldBeCalled();
        $cache->set('cb-test-lastTest', 1000, 3360)->shouldBeCalled();
        $service = new Service('test', 20, 60);
        $this->trackService($service);
        $this->reportSuccess('test');
    }

    function it_should_substract_a_failure_from_current(CacheInterface $cache)
    {
        CircuitBreakerSpec::$time = 1000;
        $cache->get('cb-test-failures')->willReturn(19);
        $cache->set('cb-test-failures', 18, 3360)->shouldBeCalled();
        $cache->set('cb-test-lastTest', 1000, 3360)->shouldBeCalled();
        $service = new Service('test', 20, 60);
        $this->trackService($service);
        $this->reportSuccess('test');
    }
}


namespace Cmp\CircuitBreaker;

/**
 * Small hack to overwrite time function in the tested object namespace
 * @return int
 */
function time()
{
    return \spec\Cmp\CircuitBreaker\CircuitBreakerSpec::$time ?: \time();
}