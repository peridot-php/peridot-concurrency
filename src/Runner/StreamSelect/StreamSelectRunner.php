<?php
namespace Peridot\Concurrency\Runner\StreamSelect;

use Peridot\Concurrency\Runner\StreamSelect\IO\WorkerInterface;
use Peridot\Concurrency\Runner\StreamSelect\IO\WorkerPoolInterface;
use Peridot\Core\HasEventEmitterTrait;
use Peridot\Core\Suite;
use Peridot\Core\Test;
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
     * @var array
     */
    protected $errors = [];

    /**
     * @var TestResult
     */
    protected $result;

    /**
     * @param EventEmitterInterface $emitter
     * @param IO\WorkerPoolInterface $pool
     */
    public function __construct(
        EventEmitterInterface $emitter,
        IO\WorkerPoolInterface $pool
    ) {
        $this->eventEmitter = $emitter;
        $this->pool = $pool;
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
        $this->result = $result;
        $start = microtime(true);
        $command = realpath(__DIR__ . '/../../../bin/select-runner');
        $this->pool->start($command);
        $this->eventEmitter->emit('runner.end', [microtime(true) - $start]);
        $this->eventEmitter->emit('peridot.concurrency.runner.end', [$this->errors]);
    }

    /**
     * Delegate suite.start message event to the Peridot event emitter.
     *
     * @param Suite $suite
     * @return void
     */
    public function onSuiteStart(Suite $suite)
    {
        $this->eventEmitter->emit('suite.start', [$suite]);
    }

    /**
     * Delegate suite.end message event to the Peridot event emitter.
     *
     * @param Suite $suite
     * @return void
     */
    public function onSuiteEnd(Suite $suite)
    {
        $this->eventEmitter->emit('suite.end', [$suite]);
    }

    /**
     * Delegate test.passed message event to a Peridot TestResult.
     *
     * @param Test $suite
     * @return void
     */
    public function onTestPassed(Test $test)
    {
        $this->result->passTest($test);
    }

    /**
     * Delegate test.failed message event to a Peridot TestResult.
     *
     * @param Test $suite
     * @param $exception - an exception like object
     * @return void
     */
    public function onTestFailed(Test $test, $exception)
    {
        $this->result->failTest($test, $exception);
    }

    /**
     * Delegate test.pending message event to a Peridot TestResult.
     *
     * @param Test $suite
     * @return void
     */
    public function onTestPending(Test $test)
    {
        $this->result->pendTest($test);
    }

    /**
     * Handle errors.
     *
     * @param $data
     */
    public function onError($error, WorkerInterface $worker)
    {
        $info = $worker->getJobInfo();
        $this->errors[$info->file] = $error;
    }

    /**
     * Get stored errors.
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Listen for start of workers.
     *
     * @param array $workers
     */
    public function onWorkersStart(WorkerPoolInterface $pool)
    {
        $workers = $pool->getWorkers();
        $count = sizeof($workers);
        $this->eventEmitter->emit('peridot.concurrency.stream-select.start', [$count]);
    }

    /**
     * Register event listeners.
     *
     * @return void
     */
    protected function listen()
    {
        $this->eventEmitter->on('peridot.concurrency.pool.start-workers', [$this, 'onWorkersStart']);
        $this->eventEmitter->on('peridot.concurrency.worker.error', [$this, 'onError']);
        $broker = $this->pool->getMessageBroker();
        $broker->on('suite.start', [$this, 'onSuiteStart']);
        $broker->on('suite.end', [$this, 'onSuiteEnd']);
        $broker->on('test.passed', [$this, 'onTestPassed']);
        $broker->on('test.failed', [$this, 'onTestFailed']);
        $broker->on('test.pending', [$this, 'onTestPending']);
    }
}
