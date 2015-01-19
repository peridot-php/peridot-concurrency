<?php
namespace Peridot\Concurrency\Runner\StreamSelect;

use Peridot\Concurrency\Configuration;
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
class StreamSelectRunner implments RunnerInterface
{
    /**
     * @var Configuration
     */
    protected $config;

    /**
     * @var EventEmitterInterface
     */
    protected $emitter;

    /**
     * @param Configuration $config
     * @param EventEmitterInterface $emitter
     */
    public function __construct(Configuration $config, EventEmitterInterface $emitter)
    {
        $this->config = $config;
        $this->emitter = $emitter;
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

    }

    /**
     * Send a suite path to the test runner.
     *
     * @param string $suitePath
     * @return void
     */
    public function onSuiteLoading($suitePath)
    {
    }

    /**
     * Setup event listeners.
     *
     * @return void
     */
    protected function listen()
    {
        $this->emitter->on('peridot.concurrency.suiteloading', [$this, 'onSuiteLoading']);
    }
}
