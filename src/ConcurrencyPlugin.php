<?php
namespace Peridot\Concurrency;

use Peridot\Concurrency\Runner\StreamSelect\IO\WorkerPool;
use Peridot\Concurrency\Runner\StreamSelect\Message\MessageBroker;
use Peridot\Concurrency\Runner\StreamSelect\StreamSelectRunner;
use Peridot\Console\Application;
use Peridot\Console\Environment;
use Peridot\Console\Command;
use Peridot\Configuration as CoreConfiguration;
use Evenement\EventEmitterInterface;
use Peridot\Reporter\ReporterFactory;
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
     * @var \Peridot\Concurrency\Configuration
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
        $this->emitter->on('peridot.reporters', [$this, 'onPeridotReporters']);
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
        $definition->option('concurrent', null, InputOption::VALUE_NONE, 'run specs concurrently');
        $definition->option('processes', 'p', InputOption::VALUE_REQUIRED, 'number of processes to use');
    }

    /**
     * Stores a reference to the peridot input interface.
     * @return void
     */
    public function onPeridotExecute(InputInterface $input)
    {
        $this->input = $input;
    }

    /**
     * Configures peridot to use Peridot\Concurrency\SuiteLoader
     * if the concurrent option is set.
     *
     * @return void
     */
    public function onPeridotLoad(Command $command, CoreConfiguration $config)
    {
        if (! $this->isConcurrencyEnabled()) {
            return;
        }

        $processes = $this->getInput()->getOption('processes');

        if ($processes) {
            $this->getConfiguration()->setProcesses((int) $processes);
        }

        $this->configureCommand($command);
        $config->setReporter('concurrent');
    }

    /**
     * Set the configuration and application references.
     *
     * @param Configuration $configuration
     * @param Application $application
     */
    public function onPeridotConfigure(CoreConfiguration $configuration, Application $application)
    {
        $this->configuration = new Configuration($configuration);
        $this->application = $application;
    }

    /**
     * Register the concurrency reporter.
     *
     * @param InputInterface $input
     * @param ReporterFactory $reporters
     */
    public function onPeridotReporters(InputInterface $input, ReporterFactory $reporters)
    {
        $reporters->register('concurrent', 'organize files by time to execute', 'Peridot\Concurrency\Reporter\ConcurrentReporter');
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

    /**
     * Configure the peridot command for concurrency.
     *
     * @param Command $command
     * @return void
     */
    protected function configureCommand(Command $command)
    {
        $broker = new MessageBroker();
        $pool = new WorkerPool($this->getConfiguration(), $this->emitter, $broker);
        $runner = new StreamSelectRunner($this->emitter, $pool);
        $command->setRunner($runner);

        $loader = new SuiteLoader($this->getConfiguration()->getGrep(), $this->emitter);
        $command->setLoader($loader);
    }
}
