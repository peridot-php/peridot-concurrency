<?php
namespace Peridot\Concurrency\Runner\StreamSelect;

use Evenement\EventEmitterInterface;
use Peridot\Concurrency\Environment\Environment;
use Peridot\Concurrency\Runner\StreamSelect\Message\TestMessage;
use Peridot\Core\Suite;
use Peridot\Core\Test;
use Peridot\Core\TestResult;
use Peridot\Runner\Context;
use Peridot\Runner\Runner;

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
     * @var TestMessage
     */
    protected $message;

    /**
     * @param Environment $environment
     */
    public function __construct(Environment $environment)
    {
        $this->environment = $environment;
    }

    /**
     * Run the application.
     *
     * @param TestMessage $message
     */
    public function run(TestMessage $message)
    {
        $this->environment->load();
        $this->message = $message;
        $this->listen($this->environment->getEventEmitter());
        $context = Context::getInstance();
        $this->loop($context);
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

    /**
     * The application loop.
     *
     * @param TestMessage $message
     * @param $context
     * @param $configuration
     * @param $emitter
     */
    protected function loop(Context $context)
    {
        while (true) {
            $input = fgets($this->environment->getReadStream());
            $path = trim($input);
            $context->setFile($path);
            require $path;

            $runner = new Runner(
                $context->getCurrentSuite(),
                $this->environment->getConfiguration(),
                $this->environment->getEventEmitter()
            );

            $runner->run(new TestResult($this->environment->getEventEmitter()));

            $this->message->end();
            $context->clear();
        }
    }
}
