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
     * @param ReaderInterface $reader
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
     * Listen for message events.
     *
     * @param EventEmitterInterface $emitter
     */
    protected function listen(EventEmitterInterface $emitter)
    {
        $emitter->on('suite.start', [$this, 'onSuiteStart']);
        $emitter->on('suite.end', [$this, 'onSuiteEnd']);
        $emitter->on('test.passed', [$this, 'onTestPassed']);
        $emitter->on('test.failed', [$this, 'onTestFailed']);
        $emitter->on('test.pending', [$this, 'onTestPending']);
    }
}
