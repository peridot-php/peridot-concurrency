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
    protected $running = [];

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
        $this->listen();
    }

    /**
     * Starts all workers and sends input to them until none
     * is left. Additionally starts polling of streams for changes.
     *
     * @return void
     */
    public function start()
    {
        $this->startWorkers();
        while ($this->isWorking()) {
            $worker = $this->getAvailableWorker();

            if (! $worker) {
                continue;
            }

            $worker->run(array_shift($this->pending));
            $this->poll();
        }
    }

    /**
     * Poll worker streams for changes. If any changes are detected, then an
     * event is emitted signaling which worker has completed.
     *
     * @return void
     */
    public function poll()
    {
        $read = $this->getReadStreams();
        $write = null;
        $except = null;
        $modified = stream_select($read, $write, $except, 0, 200000);

        if ($modified === false) {
            throw new \RuntimeException("stream_select() returned an error");
        }

        foreach ($read as $stream) {
            foreach ($this->running as $worker) {
                if ($worker->hasStream($stream)) {
                    $this->eventEmitter->emit('peridot.concurrency.worker.completed', [$worker]);
                }
            }
        }
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
     * @return WorkerInterface|null
     */
    public function getAvailableWorker()
    {
        foreach ($this->workers as $worker) {
            if (! $worker->isRunning()) {
                return $worker;
            }
        }
        return null;
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
        $this->eventEmitter->emit('peridot.concurrency.pool.start-workers', [$this]);
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

    /**
     * Return a collection of running workers.
     *
     * @return array
     */
    public function getRunning()
    {
        return $this->running;
    }

    /**
     * @param WorkerInterface $worker
     *
     * @return void
     */
    public function addRunning(WorkerInterface $worker)
    {
        $this->running[] = $worker;
    }

    /**
     * Checks if there are any pending tests or running workers.
     *
     * @return bool
     */
    public function isWorking()
    {
        $numPending = sizeof($this->getPending());
        $numRunning = sizeof($this->getRunning());
        return $numPending > 0 || $numRunning > 0;
    }

    /**
     * Free a worker and remove it from the list of running
     * workers.
     *
     * @param WorkerInterface $worker
     *
     * @return void
     */
    public function onWorkerComplete(WorkerInterface $worker)
    {
        $worker->free();
        $this->running = array_filter($this->running, function (WorkerInterface $worker) {
            return $worker->isRunning();
        });
    }

    /**
     * Set event listeners.
     *
     * @return void
     */
    protected function listen()
    {
        $this->eventEmitter->on('peridot.concurrency.load', [$this, 'setPending']);
        $this->eventEmitter->on('peridot.concurrency.worker.run', [$this, 'addRunning']);
        $this->eventEmitter->on('peridot.concurrency.worker.completed', [$this, 'onWorkerComplete']);
    }
} 
