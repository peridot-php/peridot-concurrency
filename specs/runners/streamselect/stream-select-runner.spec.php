<?php
use Peridot\Concurrency\Runner\StreamSelect\StreamSelectRunner;
use Peridot\Concurrency\Configuration;
use Peridot\Configuration as CoreConfig;
use Evenement\EventEmitter;

describe('StreamSelectRunner', function () {
    beforeEach(function () {
        $core = new CoreConfig();
        $this->configuration = new Configuration($core);
        $this->emitter = new EventEmitter();
        $this->runner = new StreamSelectRunner($this->configuration, $this->emitter);
    });

    context('when peridot.concurrency.loadstart event is emitted', function () {
        it('should set pending count on the runner', function () {
            $this->emitter->emit('peridot.concurrency.loadstart', [3]);
            expect($this->runner->getPending())->to->equal(3);
        });
    });

    describe('->attach()', function () {
        beforeEach(function () {
            $interface = 'Peridot\Concurrency\Runner\StreamSelect\WorkerInterface';
            $this->worker = $this->getProphet()->prophesize($interface);
        });

        afterEach(function () {
            $this->getProphet()->checkPredictions();
        });

        it('should start the attached worker', function () {
            $this->runner->attach($this->worker->reveal());
            $this->worker->start()->shouldBeCalled();
        });
    });
});
