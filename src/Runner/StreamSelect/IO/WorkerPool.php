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
     * @var array
     */
    protected $pending = [];

    /**
     * @var array
     */
    protected $workers = [];

    /**
     * @var ResourceOpenInterface
     */
    protected $resourceOpen;

    /**
     * @var array
     */
    protected $readStreams = [];

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
        $this->eventEmitter->on('peridot.concurrency.load', [$this, 'setPending']);
        $this->eventEmitter->on('peridot.concurrency.worker.completed', [$this, 'onWorkerComplete']);
    }

    public function start()
    {
        $this->startWorkers();
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
     * it if it is not already started. The workers output and error streams will
     * be stored and monitored for changes.
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

        $this->readStreams[] = $worker->getOutputStream();
        $this->readStreams[] = $worker->getErrorStream();

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
     * Get all streams being read from.
     *
     * @return array
     */
    public function getReadStreams()
    {
        return $this->readStreams;
    }

    /**
     * Set the pending tests.
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
