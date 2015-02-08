<?php
namespace Peridot\Concurrency\Runner\StreamSelect\IO;

use Evenement\EventEmitterInterface;
use Peridot\Concurrency\Configuration;
use Peridot\Concurrency\Runner\StreamSelect\Message\ErrorMessage;
use Peridot\Concurrency\Runner\StreamSelect\Message\Message;
use Peridot\Concurrency\Runner\StreamSelect\Message\MessageBroker;
use Peridot\Concurrency\Runner\StreamSelect\Message\TestMessage;
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
     * @var MessageBroker
     */
    protected $broker;

    /**
     * @param Configuration $configuration
     * @param EventEmitterInterface $eventEmitter
     * @param MessageBroker $broker
     * @param ResourceOpenInterface $resourceOpen
     */
    public function __construct(
        Configuration $configuration,
        EventEmitterInterface $eventEmitter,
        MessageBroker $broker,
        ResourceOpenInterface $resourceOpen = null
    ) {
        $this->configuration = $configuration;
        $this->eventEmitter = $eventEmitter;
        $this->broker = $broker;
        $this->resourceOpen = $resourceOpen;
        $this->listen();
    }

    /**
     * {@inheritdoc}
     *
     * @param string $command
     * @return void
     */
    public function start($command)
    {
        $this->startWorkers($command);
        while ($this->isWorking()) {
            $worker = $this->getAvailableWorker();

            if ($worker && $this->pending) {
                $worker->run(array_shift($this->pending));
            }

            $this->broker->read();
        }
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
     * @param string $command
     * @return void
     */
    public function startWorkers($command)
    {
        $processes = $this->configuration->getProcesses();
        for ($i = 0; $i < $processes; $i++) {
            $worker = new Worker($command, $this->eventEmitter, $this->resourceOpen);
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

        $this->broker->addMessage(new TestMessage($worker->getOutputStream()));
        $this->broker->addMessage(new ErrorMessage($worker->getErrorStream()));

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
        return $this->broker->getStreams();
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
     * {@inheritdoc}
     *
     * @return MessageBroker
     */
    public function getMessageBroker()
    {
        return $this->broker;
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
     * Match a running worker to a message resource and
     * emit a completed event for that worker.
     *
     * @param Message $message
     */
    public function onMessageEnd(Message $message)
    {
        foreach ($this->running as $worker) {
            if ($worker->hasStream($message->getResource())) {
                $worker->getJobInfo()->end = microtime(true);
                $this->eventEmitter->emit('peridot.concurrency.worker.completed', [$worker]);
            }
        }
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
        $this->broker->on('end', [$this, 'onMessageEnd']);
    }
}
