<?php
use Evenement\EventEmitterInterface;
use Peridot\Concurrency\ConcurrencyPlugin;
use Peridot\Console\Environment;
use Peridot\Plugin\Prophecy\ProphecyPlugin;

return function (EventEmitterInterface $emitter) {
    $prophecy = new ProphecyPlugin($emitter);
    $concurrency = new ConcurrencyPlugin($emitter);

    $emitter->on('peridot.start', function (Environment $env) {
        $definition = $env->getDefinition();
        $definition->getArgument('path')->setDefault('specs');
    });
};
