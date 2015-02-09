<?php
namespace Peridot\Concurrency\Reporter;

use Peridot\Concurrency\Runner\StreamSelect\IO\Worker;
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
     * @var array
     */
    protected $times = [];

    /**
     * @var int
     */
    protected $failures = 0;

    /**
     * @var int
     */
    protected $successes = 0;

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
        $this->eventEmitter->on('peridot.concurrency.worker.completed', [$this, 'onWorkerCompleted']);
        $this->eventEmitter->on('peridot.concurrency.pool.complete', [$this, 'footer']);
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
     * Track the time it took a worker to complete.
     *
     * @param Worker $worker
     */
    public function onWorkerCompleted(Worker $worker)
    {
        $info = $worker->getJobInfo();
        $this->times[$info->file] = $info->end - $info->start;
        $data = $this->suites[$info->file];

        if ($data) {
            $this->writeTestReport($this->suites[$info->file]);
        }
    }

    /**
     * Writes a test message to output.
     *
     * @param array $tests
     */
    public function writeTestReport(array $tests)
    {
        $failed = $this->isFailedSuite($tests);

        if ($failed) {
            $this->failures++;
        }

        if (!$failed) {
            $this->successes++;
        }

        $this->writeTestHeader($tests[0]['test']->getFile(), $failed);
        $this->writeTestFailures($tests);
    }

    /**
     * Writes the header for a completed test file.
     *
     * @param string $path
     * @param bool $failed
     */
    public function writeTestHeader($path, $failed)
    {
        $heading = $this->color('success', ' PASS ');
        if ($failed) {
            $heading = $this->color('error', ' FAIL ');
        }

        $time = $this->getTimeFor($path);
        $this->output->writeln($heading . ' ' . $path . ' (' . \PHP_Timer::secondsToTimeString($time) . ')');
    }

    /**
     * Write test failures if any.
     *
     * @param array $tests
     */
    public function writeTestFailures(array $tests)
    {
        $failures = array_filter($tests, function($test) {
            return !is_null($test['exception']);
        });

        $failures = array_values($failures);
        $numFailures = count($failures);

        for ($i = 0; $i < $numFailures; $i++) {
            $entry = $failures[$i];
            $this->outputError($i + 1, $entry['test'], $entry['exception']);
        }
    }

    /**
     * Output test stats.
     */
    public function footer()
    {
        if ($this->failures) {
            $this->output->write($this->getCountString($this->failures, true));
        }

        if ($this->failures && $this->successes) {
            $this->output->write(',');
        }

        $this->output->write($this->getCountString($this->successes, false));
        $this->output->writeln(sprintf(' (%d total)', $this->failures + $this->successes));

        $totalTime = array_reduce($this->times, function ($r, $i) {
            return $i + $r;
        }, 0);

        $this->output->writeln(sprintf(' Run time: %s', \PHP_Timer::secondsToTimeString($totalTime)));
    }

    /**
     * Get the time taken to run tests in the file
     * identified by $path.
     *
     * @param $path
     * @return float
     */
    public function getTimeFor($path)
    {
        return $this->times[$path];
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
     * Return the number of failed suites.
     *
     * @return int
     */
    public function getFailureCount()
    {
        return $this->failures;
    }

    /**
     * Return the number of successful suites.
     *
     * @return int
     */
    public function getSuccessCount()
    {
        return $this->successes;
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

    /**
     * @param array $tests
     * @return bool
     */
    protected function isFailedSuite(array $tests)
    {
        $failed = false;

        foreach ($tests as $test) {
            if ($test['exception']) {
                $failed = true;
                break;
            }
        }

        return $failed;
    }

    /**
     * Get a string for outputting a count of passing
     * or failing tests.
     *
     * @param $num
     * @param $failed
     * @return string
     */
    private function getCountString($num, $failed)
    {
        $label = "$num test";
        if ($num != 1) {
            $label .= 's';
        }

        if ($failed) {
            return $this->color('error', " $label failed");
        }

        return $this->color('success', " $label passed");
    }
}
