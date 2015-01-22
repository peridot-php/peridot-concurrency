<?php
namespace Peridot\Concurrency\Runner\StreamSelect;

use Peridot\Core\HasEventEmitterTrait;
use Peridot\Runner\RunnerInterface;
use Peridot\Core\TestResult;
use Evenement\EventEmitterInterface;

/**
 * The default runner for the concurrency package. The StreamSelectRunner
 * makes use of non blocking file streams and the stream_select function
 * to watch for changes.
 *
 * @package Peridot\Concurrency\Runner\StreamSelect
 */
class StreamSelectRunner implements RunnerInterface
{
    use HasEventEmitterTrait;

    /**
     * @var IO\WorkerPool
     */
    protected $pool;

    /**
     * @param EventEmitterInterface $emitter
     * @param IO\WorkerPool $pool
     */
    public function __construct(
        EventEmitterInterface $emitter,
        IO\WorkerPool $pool
    ) {
        $this->eventEmitter = $emitter;
        $this->pool = $pool;
        $this->eventEmitter->on('peridot.concurrency.suiteloading', [$this, 'onSuiteLoading']);
    }

    /**
     * Listen for suite loading events and run those suites concurrently.
     *
     * @param TestResult $resut
     * @return void
     */
    public function run(TestResult $result)
    {
        $this->pool->startWorkers();
    }

    /**
     * Send a suite path to the test runner.
     *
     * @param string $suitePath
     * @return void
     */
    public function onSuiteLoading($suitePath)
    {
    }
}
