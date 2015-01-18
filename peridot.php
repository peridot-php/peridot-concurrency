<?php
use Evenement\EventEmitterInterface;
use Peridot\Console\Environment;

return function (EventEmitterInterface $emitter) {
    $emitter->on('peridot.start', function (Environment $env) {
        $definition = $env->getDefinition();
        $definition->getArgument('path')->setDefault('specs');
    });
};
