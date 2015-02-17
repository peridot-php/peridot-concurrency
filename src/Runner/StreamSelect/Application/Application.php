<?php
namespace Peridot\Concurrency\Runner\StreamSelect\Application;

use Evenement\EventEmitterInterface;
use Peridot\Concurrency\Environment\Environment;
use Peridot\Concurrency\Environment\ReaderInterface;
use Peridot\Concurrency\Runner\StreamSelect\Message\TestMessage;
use Peridot\Core\Suite;
use Peridot\Core\Test;
use Peridot\Runner\Context;

/**
 * The StreamSelect Application runs for each worker process and
 * writes data to listening messages.
 *
 * @package Peridot\Concurrency\Runner\StreamSelect
 */
class Application
{
    /**
     * @var Environment
     */
    protected $environment;

    /**
     * @var ReaderInterface
     */
    protected $reader;

    /**
     * @var LooperInterface
     */
    protected $looper;

    /**
     * @var TestMessage
     */
    protected $message;

    /**
     * @param Environment $environment
     * @param ReaderInterface $reader
     */
    public function __construct(
        Environment $environment,
        ReaderInterface $reader,
        LooperInterface $looper
    ) {
        $this->environment = $environment;
        $this->reader = $reader;
        $this->looper = $looper;
    }

    /**
     * Run the application.
     *
     * @param TestMessage $message
     * @return void
     */
    public function run(TestMessage $message)
    {
        $this->environment->load($this->reader);
        $this->message = $message;
        $this->listen($this->environment->getEventEmitter());
        $context = Context::getInstance();
        $this->looper->loop($context, $this->environment, $message);
    }

    /**
     * Write a suite.start event on the message.
     *
     * @param Suite $suite
     * @return void
     */
    public function onSuiteStart(Suite $suite)
    {
        if (! $suite->getDescription()) {
            return;
        }

        $this->message
            ->setTest($suite)
            ->setEvent('suite.start')
            ->write();
    }

    /**
     * Write a suite.end event on the message.
     *
     * @param Suite $suite
     * @return void
     */
    public function onSuiteEnd(Suite $suite)
    {
        if (! $suite->getDescription()) {
            return;
        }

        $this->message
            ->setTest($suite)
            ->setEvent('suite.end')
            ->write();
    }

    /**
     * Write a test.passed event on the message.
     *
     * @param Test $test
     * @return void
     */
    public function onTestPassed(Test $test)
    {
        $this->message
            ->setTest($test)
            ->setEvent('test.passed')
            ->setStatus(TestMessage::TEST_PASS)
            ->write();
    }

    /**
     * Write a test.failed event on the message.
     *
     * @param Test $test
     * @param $exception
     * @return void
     */
    public function onTestFailed(Test $test, $exception)
    {
        $this->message
            ->setTest($test)
            ->setEvent('test.failed')
            ->setStatus(TestMessage::TEST_FAIL)
            ->setException($exception)
            ->write();
    }

    /**
     * Write a test.pending event on the message.
     *
     * @param Test $test
     * @return void
     */
    public function onTestPending(Test $test)
    {
        $this->message
            ->setTest($test)
            ->setEvent('test.pending')
            ->setStatus(TestMessage::TEST_PENDING)
            ->write();
    }

    /**
     * Write a suite.halt event on the message.
     *
     * @return void
     */
    public function onSuiteHalt()
    {
        $this->message
            ->setEvent('suite.halt')
            ->write();
    }

    /**
     * Listen for message events.
     *
     * @param EventEmitterInterface $emitter
     * @return void
     */
    protected function listen(EventEmitterInterface $emitter)
    {
        $emitter->on('suite.start', [$this, 'onSuiteStart']);
        $emitter->on('suite.end', [$this, 'onSuiteEnd']);
        $emitter->on('test.passed', [$this, 'onTestPassed']);
        $emitter->on('test.failed', [$this, 'onTestFailed']);
        $emitter->on('test.pending', [$this, 'onTestPending']);
        $emitter->on('suite.halt', [$this, 'onSuiteHalt']);
    }
}
