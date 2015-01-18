<?php
namespace Peridot\Concurrency;

use Peridot\Console\Environment;
use Evenement\EventEmitterInterface;
use Symfony\Component\Console\Input\InputOption;

class ConcurrencyPlugin
{
    /**
     * @var EventEmitterInterface
     */
    private $emitter;

    /**
     * @param EventEmitterInterface $emitter
     */
    public function __construct(EventEmitterInterface $emitter)
    {
        $this->emitter = $emitter;
        $this->emitter->on('peridot.start', [$this, 'onPeridotStart']);
    }

    /**
     * Registers a --concurrent option with peridot
     *
     * @param Environment $env
     */
    public function onPeridotStart(Environment $env)
    {
        $definition = $env->getDefinition();
        $definition->option('concurrent', null, InputOption::VALUE_NONE, "run specs concurrently");
    }
}
