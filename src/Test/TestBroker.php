<?php
namespace Peridot\Concurrency\Test;

use Evenement\EventEmitter;
use Peridot\Concurrency\Runner\StreamSelect\IO\WorkerPool;
use Peridot\Concurrency\Runner\StreamSelect\Message\MessageBroker;

/**
 * Class TestBroker
 *
 * Used for testing the pool's start method.
 */
class TestBroker extends MessageBroker
{
    /**
     * @var WorkerPool
     */
    private $pool;

    /**
     * @var EventEmitter
     */
    private $emitter;

    /**
     * @param EventEmitter $emitter
     */
    public function __construct(EventEmitter $emitter)
    {
        $this->emitter = $emitter;
    }

    /**
     * @param WorkerPool $pool
     */
    public function setPool(WorkerPool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * This read sets the pending test count to 0
     * and immediately frees all workers.
     */
    public function read()
    {
        $this->pool->setPending([]);
        $workers = $this->pool->getWorkers();
        foreach ($workers as $worker) {
            $this->emitter->emit('peridot.concurrency.worker.completed', [$worker]);
        }
    }
} 
