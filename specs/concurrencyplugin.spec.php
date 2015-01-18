<?php
use Peridot\Concurrency\ConcurrencyPlugin;
use Peridot\Console\InputDefinition;
use Peridot\Console\Environment;
use Evenement\EventEmitter;

describe('ConcurrencyPlugin', function () {
    beforeEach(function () {
        $this->emitter = new EventEmitter();
        $this->plugin = new ConcurrencyPlugin($this->emitter);
    });

    context('when peridot.start event is emitted', function () {
        beforeEach(function () {
            $this->definition = new InputDefinition();
            $this->environment = new Environment($this->definition, $this->emitter, []);
        });

        it('should register a --concurrent option', function () {
            $this->emitter->emit('peridot.start', [$this->environment]);
            expect($this->definition->hasOption('concurrent'))->to->be->true;
        });
    });
});
