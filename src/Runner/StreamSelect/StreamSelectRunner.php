<?php
namespace Peridot\Concurrency\Runner\StreamSelect;

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
        $start = microtime(true);
        $command = realpath(__DIR__ . '/../../../bin/select-runner');
        $this->pool->start($command);
        $this->eventEmitter->emit('runner.end');
        $this->eventEmitter->emit('peridot.concurrency.runner.end', [microtime(true) - $start]);
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
     * Delegate test.passed message event to the Peridot event emitter.
     *
     * @param Test $suite
     * @return void
     */
    public function onTestPassed(Test $test)
    {
        $this->eventEmitter->emit('test.passed', [$test]);
    }

    /**
     * Delegate test.failed message event to the Peridot event emitter.
     *
     * @param Test $suite
     * @param $exception - an exception like object
     * @return void
     */
    public function onTestFailed(Test $test, $exception)
    {
        $this->eventEmitter->emit('test.failed', [$test, $exception]);
    }

    /**
     * Delegate test.pending message event to the Peridot event emitter.
     *
     * @param Test $suite
     * @return void
     */
    public function onTestPending(Test $test)
    {
        $this->eventEmitter->emit('test.pending', [$test]);
    }

    /**
     * Handle errors.
     *
     * @param $data
     */
    public function onError($data)
    {
        print "\nERROR ERROR ERROR! ABORT ABORT ABORT!\n\n\n\n";
        var_dump($data);
    }

    /**
     * Register event listeners.
     *
     * @return void
     */
    protected function listen()
    {
        $broker = $this->pool->getMessageBroker();
        $broker->on('suite.start', [$this, 'onSuiteStart']);
        $broker->on('suite.end', [$this, 'onSuiteEnd']);
        $broker->on('test.passed', [$this, 'onTestPassed']);
        $broker->on('test.failed', [$this, 'onTestFailed']);
        $broker->on('test.pending', [$this, 'onTestPending']);
        $broker->on('error', [$this, 'onError']);
    }
}
