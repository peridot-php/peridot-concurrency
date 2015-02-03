<?php
namespace Peridot\Concurrency;

use Peridot\Concurrency\Runner\StreamSelect\IO\WorkerPool;
use Peridot\Concurrency\Runner\StreamSelect\Message\MessageBroker;
use Peridot\Concurrency\Runner\StreamSelect\StreamSelectRunner;
use Peridot\Console\Application;
use Peridot\Console\Environment;
use Peridot\Console\Command;
use Peridot\Configuration;
use Evenement\EventEmitterInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;

class ConcurrencyPlugin
{
    /**
     * @var EventEmitterInterface
     */
    private $emitter;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var \Peridot\Configuration
     */
    private $configuration;

    /**
     * @var \Peridot\Console\Application
     */
    private $application;

    /**
     * @param EventEmitterInterface $emitter
     */
    public function __construct(EventEmitterInterface $emitter)
    {
        $this->emitter = $emitter;
        $this->emitter->on('peridot.start', [$this, 'onPeridotStart']);
        $this->emitter->on('peridot.execute', [$this, 'onPeridotExecute']);
        $this->emitter->on('peridot.load', [$this, 'onPeridotLoad']);
        $this->emitter->on('peridot.configure', [$this, 'onPeridotConfigure']);
    }

    /**
     * Registers a --concurrent option with peridot
     *
     * @param Environment $env
     * @return void
     */
    public function onPeridotStart(Environment $env)
    {
        $definition = $env->getDefinition();
        $definition->option('concurrent', null, InputOption::VALUE_NONE, "run specs concurrently");
    }

    /**
     * Stores a reference to the peridot input interface.
     * @return void
     */
    public function onPeridotExecute(InputInterface $input)
    {
        $this->input = $input;

        if ($this->isConcurrencyEnabled()) {
            $broker = new MessageBroker();
            $pool = new WorkerPool($this->getConfiguration(), $this->emitter, $broker);
            $runner = new StreamSelectRunner($this->emitter, $pool);
            $this->getApplication()->setRunner($runner);
        }
    }

    /**
     * Configures peridot to use Peridot\Concurrency\SuiteLoader
     * if the concurrent option is set.
     *
     * @return void
     */
    public function onPeridotLoad(Command $command, Configuration $configuration)
    {
        if (! $this->isConcurrencyEnabled()) {
            return;
        }

        $loader = new SuiteLoader($configuration->getGrep(), $this->emitter);
        $command->setLoader($loader);
    }

    /**
     * Set the configuration and application references.
     *
     * @param Configuration $configuration
     * @param Application $application
     */
    public function onPeridotConfigure(Configuration $configuration, Application $application)
    {
        $this->configuration = $configuration;
        $this->application = $application;
    }

    /**
     * Returns the store input interface reference.
     *
     * @return InputInterface
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * Return the configuration reference stored by the plugin.
     *
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * Return the application reference stored by the plugin.
     *
     * @return \Peridot\Console\Application
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * Check to see if concurrency is enabled.
     *
     * @return bool
     */
    private function isConcurrencyEnabled()
    {
        $input = $this->getInput();
        if (! $input->getOption('concurrent')) {
            return false;
        }
        return true;
    }
}
