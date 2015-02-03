<?php
use Peridot\Concurrency\ConcurrencyPlugin;
use Peridot\Console\InputDefinition;
use Peridot\Console\Environment;
use Peridot\Console\Command;
use Peridot\Core\Suite;
use Peridot\Configuration;
use Peridot\Reporter\ReporterFactory;
use Peridot\Runner\Runner;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Evenement\EventEmitter;

describe('ConcurrencyPlugin', function () {
    beforeEach(function () {
        $this->emitter = new EventEmitter();
        $this->plugin = new ConcurrencyPlugin($this->emitter);
        $this->definition = new InputDefinition();
        $this->environment = new Environment($this->definition, $this->emitter, []);
    });

    context('when peridot.start event is emitted', function () {
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

    context('when peridot.load event is emitted', function () {
        beforeEach(function () {
            $suite = new Suite("suite", function () {});
            $configuration = new Configuration();
            $runner = new Runner($suite, $configuration, $this->emitter);
            $factory = new ReporterFactory($configuration, new NullOutput(), $this->emitter);
            $this->command = new Command($runner, $configuration, $factory, $this->emitter);
            $this->configuration = $configuration;
        });

        it('should set the suite loader if conncurrent option is set', function () {
            $this->emitter->emit('peridot.start', [$this->environment]);
            $input = new StringInput('--concurrent', $this->definition);
            $this->emitter->emit('peridot.execute', [$input]);
            $this->emitter->emit('peridot.load', [$this->command, $this->configuration]);
            $loader = $this->command->getLoader();
            expect($loader)->to->be->an->instanceof('Peridot\Concurrency\SuiteLoader');
        });

        it('should not set the suite loader if concurrent options is not set', function () {
            $this->emitter->emit('peridot.start', [$this->environment]);
            $input = new StringInput('');
            $input->bind($this->definition);
            $this->emitter->emit('peridot.execute', [$input]);
            $this->emitter->emit('peridot.load', [$this->command, $this->configuration]);
            $loader = $this->command->getLoader();
            expect($loader)->to->be->an->instanceof('Peridot\Runner\SuiteLoader');
        });
    });

    context('when peridot.configure event is fired', function () {
        it('should store references to the configuration and application objects', function () {
            $app = $this->getProphet()->prophesize('Peridot\Console\Application')->reveal();
            $config = new Configuration();
            $this->emitter->emit('peridot.configure', [$config, $app]);
            expect($this->plugin->getConfiguration())->to->equal($config);
            expect($this->plugin->getApplication())->to->equal($app);
        });
    });
});
