<?php
namespace Peridot\Concurrency;

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
     * @param EventEmitterInterface $emitter
     */
    public function __construct(EventEmitterInterface $emitter)
    {
        $this->emitter = $emitter;
        $this->emitter->on('peridot.start', [$this, 'onPeridotStart']);
        $this->emitter->on('peridot.execute', [$this, 'onPeridotExecute']);
        $this->emitter->on('peridot.load', [$this, 'onPeridotLoad']);
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
    }

    /**
     * Configures peridot to use Peridot\Concurrency\SuiteLoader
     * if the concurrent option is set.
     *
     * @return void
     */
    public function onPeridotLoad(Command $command, Configuration $configuration)
    {
        $input = $this->getInput();
        if (! $input->getOption('concurrent')) {
            return;
        }
        $loader = new SuiteLoader($configuration->getGrep(), $this->emitter);
        $command->setLoader($loader);
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
}
