<?php
use Evenement\EventEmitterInterface;
use Peridot\Concurrency\ConcurrencyPlugin;
use Peridot\Console\Environment;
use Peridot\Plugin\Prophecy\ProphecyPlugin;
use Peridot\Reporter\Dot\DotReporterPlugin;

return function (EventEmitterInterface $emitter) {
    $prophecy = new ProphecyPlugin($emitter);
    $concurrency = new ConcurrencyPlugin($emitter);
    $dot = new DotReporterPlugin($emitter);

    $emitter->on('peridot.start', function (Environment $env) {
        $definition = $env->getDefinition();
        $definition->getArgument('path')->setDefault('specs');
    });
};
