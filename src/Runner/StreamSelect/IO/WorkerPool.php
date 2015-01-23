<?php
namespace Peridot\Concurrency\Runner\StreamSelect\IO;

use Evenement\EventEmitterInterface;
use Peridot\Concurrency\Configuration;
use Peridot\Core\HasEventEmitterTrait;

/**
 * The WorkerPool manages open worker processes.
 *
 * @package Peridot\Concurrency\Runner\StreamSelect\IO
 */
class WorkerPool
{
    use HasEventEmitterTrait;

    /**
     * @var \Peridot\Concurrency\Configuration
     */
    protected $configuration;

    /**
     * @var int
     */
    protected $pending = 0;

    /**
     * @var array
     */
    protected $workers = [];

    /**
     * @var ResourceOpenInterface
     */
    protected $resourceOpen;

    /**
     * @param Configuration $configuration
     * @param EventEmitterInterface $eventEmitter
     * @param ResourceOpenInterface $resourceOpen
     */
    public function __construct(
        Configuration $configuration,
        EventEmitterInterface $eventEmitter,
        ResourceOpenInterface $resourceOpen = null
    ) {
        $this->configuration = $configuration;
        $this->eventEmitter = $eventEmitter;
        $this->resourceOpen = $resourceOpen;
        $this->eventEmitter->on('peridot.concurrency.loadstart', [$this, 'setPending']);
    }

    public function start()
    {
        $this->startWorkers();
        $this->eventEmitter->on('peridot.concurrency.suiteloading', [$this, 'onSuiteLoading']);
        $this->eventEmitter->on('peridot.concurrency.worker.completed', [$this, 'onWorkerComplete']);
    }

    /**
     * @param string $path
     */
    public function onSuiteLoading($path)
    {
        $worker = $this->getAvailableWorker();
        $worker->run($path);
    }

    /**
     * Get the next available worker.
     *
     * @return WorkerInterface
     */
    public function getAvailableWorker()
    {
        $available = null;
        while (is_null($available)) {
            foreach ($this->workers as $worker) {
                if (! $worker->isRunning()) {
                    $available = $worker;
                    break;
                }
            }
        }
        return $available;
    }

    /**
     * Start worker processes, attaching worker processes
     * to fill the number of configured processes.
     *
     * @return void
     */
    public function startWorkers()
    {
        $processes = $this->configuration->getProcesses();
        for ($i = 0; $i < $processes; $i++) {
            $exec = __DIR__ . '/select-runner.php';
            $worker = new Worker("php $exec", $this->eventEmitter, $this->resourceOpen);
            $this->attach($worker);
        }
    }

    /**
     * Attach a worker to the WorkerPool and start
     * it if it is not already started.
     *
     * @return bool
     */
    public function attach(WorkerInterface $worker)
    {
        if (sizeof($this->workers) === $this->configuration->getProcesses()) {
            return false;
        }

        $this->workers[] = $worker;

        if (! $worker->isStarted()) {
            $worker->start();
        }

        return true;
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
} 
