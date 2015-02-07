<?php
namespace Peridot\Concurrency\Reporter;

use Peridot\Core\AbstractTest;
use Peridot\Core\Suite;
use Peridot\Core\Test;
use Peridot\Reporter\AbstractBaseReporter;

class ConcurrentReporter extends AbstractBaseReporter
{
    /**
     * @var array
     */
    protected $suites = [];

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function init()
    {
        $this->eventEmitter->on('suite.start', [$this, 'onSuiteStart']);
        $this->eventEmitter->on('test.passed', [$this, 'onTestPassed']);
        $this->eventEmitter->on('test.failed', [$this, 'onTestFailed']);
        $this->eventEmitter->on('test.pending', [$this, 'onTestPending']);
    }

    /**
     * Track a suite file path.
     *
     * @param Suite $suite
     */
    public function onSuiteStart(Suite $suite)
    {
        $this->trackTest($suite);
    }

    /**
     * Track a passing test.
     *
     * @param Test $test
     */
    public function onTestPassed(Test $test)
    {
        $this->suites[$test->getFile()][] = [
            'test' => $test,
            'exception' => null
        ];
     }

    /**
     * Track a test failure.
     *
     * @param Test $test
     * @param $exception
     */
    public function onTestFailed(Test $test, $exception)
    {
        $this->suites[$test->getFile()][] = [
            'test' => $test,
            'exception' => $exception
        ];
    }

    /**
     * Track a pending test.
     *
     * @param Test $test
     */
    public function onTestPending(Test $test)
    {
        $this->suites[$test->getFile()][] = [
            'test' => $test,
            'exception' => null
        ];
    }

    /**
     * Get test files being tracked by this reporter.
     *
     * @return array
     */
    public function getSuites()
    {
        return $this->suites;
    }

    /**
     * Track a test path.
     *
     * @param AbstractTest $test
     * @return void
     */
    protected function trackTest(AbstractTest $test)
    {
        $file = $test->getFile();
        if (! isset($this->suites[$file])) {
            $this->suites[$file] = [];
        }
    }
}
