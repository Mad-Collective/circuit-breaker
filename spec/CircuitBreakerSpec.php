<?php
namespace spec\Cmp\CircuitBreaker;

use Psr\SimpleCache\CacheInterface;
use PhpSpec\ObjectBehavior;

class CircuitBreakerSpec extends ObjectBehavior
{
  public function let(CacheInterface $cache)
  {
    $this->beConstructedWith($cache);
  }
}
