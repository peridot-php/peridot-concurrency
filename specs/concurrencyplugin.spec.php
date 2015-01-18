<?php
use Peridot\Concurrency\ConcurrencyPlugin;
use Peridot\Console\InputDefinition;
use Peridot\Console\Environment;
use Symfony\Component\Console\Input\StringInput;
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

    context('when peridot.execute event is emitted', function () {
        it('should store a reference to input object' , function () {
            $input = new StringInput('');
            $this->emitter->emit('peridot.execute', [$input]);
            expect($this->plugin->getInput())->to->equal($input);
        });
    });
});
