<?php
namespace Peridot\Concurrency\Runner\StreamSelect;

use Peridot\Concurrency\Configuration;
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
     * @var Configuration
     */
    protected $config;

    /**
     * @var EventEmitterInterface
     */
    protected $emitter;

    /**
     * @var int
     */
    protected $pending = 0;

    /**
     * @var array
     */
    protected $workers = [];

    /**
     * @var IO\ResourceOpenInterface
     */
    protected $resourceOpen;

    /**
     * @param Configuration $config
     * @param EventEmitterInterface $emitter
     */
    public function __construct(
        Configuration $config,
        EventEmitterInterface $emitter,
        IO\ResourceOpenInterface $resourceOpen = null
    ) {
        $this->config = $config;
        $this->eventEmitter = $emitter;
        $this->resourceOpen = $resourceOpen ?: new IO\ProcOpen();
        $this->listen();
    }

    /**
     * Listen for suite loading events and run those suites concurrently.
     *
     * @param TestResult $resut
     * @return void
     */
    public function run(TestResult $result)
    {
        $this->startWorkers();
    }

    /**
     * Attach a worker to the StreamSelectRunner and start
     * it if it is not already started.
     *
     * @return bool
     */
    public function attach(IO\WorkerInterface $worker)
    {
        if (sizeof($this->workers) === $this->config->getProcesses()) {
            return false;
        }

        $this->workers[] = $worker;

        if (! $worker->isStarted()) {
            $worker->start();
        }

        return true;
    }

    /**
     * Start worker processes.
     *
     * @return void 
     */
    public function startWorkers()
    {
        $processes = $this->config->getProcesses();
        for ($i = 0; $i < $processes; $i++) {
            $exec = __DIR__ . '/select-runner.php';
            $worker = new IO\Worker("php $exec", $this->eventEmitter, $this->resourceOpen);
            $this->attach($worker);
        }
    }

    /**
     * Set the number of pending tests.
     *
     * @param int $pending
     * @return void
     */
    public function setPending($pending)
    {
        $this->pending = $pending;
    }

    /**
     * Get the number of pending tests.
     *
     * @return int
     */
    public function getPending()
    {
        return $this->pending;
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

    /**
     * Get all workers attached to the runner.
     *
     * @return array
     */
    public function getWorkers()
    {
        return $this->workers;
    }

    /**
     * Setup event listeners.
     *
     * @return void
     */
    protected function listen()
    {
        $this->eventEmitter->on('peridot.concurrency.loadstart', [$this, 'setPending']);
        $this->eventEmitter->on('peridot.concurrency.suiteloading', [$this, 'onSuiteLoading']);
    }
}
