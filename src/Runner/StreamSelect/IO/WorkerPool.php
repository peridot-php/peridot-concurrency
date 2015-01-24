<?php
namespace Peridot\Concurrency\Runner\StreamSelect\IO;

use Evenement\EventEmitterInterface;
use Peridot\Concurrency\Configuration;
use Peridot\Core\HasEventEmitterTrait;

/**
 * An evented WorkerPool for managing worker processes.
 *
 * @package Peridot\Concurrency\Runner\StreamSelect\IO
 */
class WorkerPool implements WorkerPoolInterface
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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

        $this->freeStreams($read);
    }

    /**
     * {@inheritdoc}
     *
     * @return null|WorkerInterface
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
     * {@inheritdoc}
     *
     * If any changes are detected, then an
     * event is emitted signaling which worker has completed.
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
     * {@inheritdoc}
     *
     * @param WorkerInterface $worker
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
     * {@inheritdoc}
     *
     * @return array
     */
    public function getWorkers()
    {
        return $this->workers;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getReadStreams()
    {
        return $this->readStreams;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $pending
     */
    public function setPending($pending)
    {
        $this->pending = $pending;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getPending()
    {
        return $this->pending;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRunning()
    {
        return $this->running;
    }

    /**
     * {@inheritdoc}
     *
     * @param WorkerInterface $worker
     */
    public function addRunning(WorkerInterface $worker)
    {
        $this->running[] = $worker;
    }

    /**
     * {@inheritdoc}
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
     * Frees a worker and removes workers that are not running
     * from the internal collection of running workers.
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

    /**
     * Attempts to free workers by matching a modified stream
     * to the worker that owns it. Emits a completed event that signals
     * a worker should be freed.
     *
     * @param array $modified - an array of modified read streams.
     * @return void
     */
    protected function freeStreams($modified)
    {
        foreach ($modified as $stream) {
            foreach ($this->running as $worker) {
                if ($worker->hasStream($stream)) {
                    $this->eventEmitter->emit('peridot.concurrency.worker.completed', [$worker]);
                }
            }
        }
    }
} 
