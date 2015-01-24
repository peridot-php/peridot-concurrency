<?php
namespace Peridot\Concurrency;

use Peridot\Runner\SuiteLoader as CoreLoader;
use Peridot\Core\HasEventEmitterTrait;
use Evenement\EventEmitterInterface;

/**
 * An event driven version of the core SuiteLoader. Rather than include
 * globbed tests, this SuiteLoader emits an event with the suite path
 * when the suite is found.
 *
 * @package Peridot\Concurrency
 */
class SuiteLoader extends CoreLoader
{
    use HasEventEmitterTrait;

    /**
     * @param string $pattern
     * @param EventEmitterInterface $eventEmitter
     */
    public function __construct($pattern, EventEmitterInterface $eventEmitter)
    {
        parent::__construct($pattern);
        $this->eventEmitter = $eventEmitter;
    }

    /**
     * Overrides the default load behavior to just emit suite paths
     * rather than include them.
     *
     * @param string $path
     * @return void
     */
    public function load($path)
    {
        $tests = $this->getTests($path);
        $this->eventEmitter->emit('peridot.concurrency.load', [$tests]);
    }
}
