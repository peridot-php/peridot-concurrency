<?php
use Peridot\Concurrency\Runner\StreamSelect\StreamSelectRunner;
use Peridot\Concurrency\Runner\StreamSelect\TmpfileOpen;
use Peridot\Concurrency\Configuration;
use Peridot\Configuration as CoreConfig;
use Evenement\EventEmitter;

describe('StreamSelectRunner', function () {
    beforeEach(function () {
        $core = new CoreConfig();
        $this->configuration = new Configuration($core);
        $this->configuration->setProcesses(2);
        $this->emitter = new EventEmitter();

        $open = new TmpfileOpen();
        $this->runner = new StreamSelectRunner($this->configuration, $this->emitter, $open);
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
            $this->workers = [];
            for ($i = 0; $i <= $this->configuration->getProcesses(); $i++) {
                $this->workers[] = $this->getProphet()->prophesize($interface);
            }
        });

        afterEach(function () {
            $this->getProphet()->checkPredictions();
        });

        it('should start the attached worker', function () {
            $this->runner->attach($this->workers[0]->reveal());
            $this->workers[0]->start()->shouldBeCalled();
        });

        it('should not start the worker if it is already started', function () {
            $this->workers[1]->isStarted()->willReturn(true);
            $this->runner->attach($this->workers[1]->reveal());
            $this->workers[1]->start()->shouldNotBeCalled();
        });

        it('should return true when a worker is attached', function () {
            $attached = $this->runner->attach($this->workers[0]->reveal());
            expect($attached)->to->be->true;
        });

        it('should return false when attempting to attach a worker beyond process count', function () {
            $processes = $this->configuration->getProcesses();
            for ($i = 0; $i < $processes; $i++) {
                $attached = $this->runner->attach($this->workers[$i]->reveal());
                expect($attached)->to->be->true;
            }
            $attached = $this->runner->attach($this->workers[$processes]);
            expect($attached)->to->be->false();
        });
    });

    describe('->startWorkers()', function () {
        it('should attach workers for the number of processes', function () {
            $this->runner->startWorkers();
            $processes = $this->configuration->getProcesses();
            expect($this->runner->getWorkers())->to->have->length($processes);
        });

        context('when workers are already attached', function () {
            it('should not add additional workers', function () {
                $interface = 'Peridot\Concurrency\Runner\StreamSelect\WorkerInterface';
                $worker = $this->getProphet()->prophesize($interface);
                $this->runner->attach($worker->reveal());
                $this->runner->startWorkers();
                $processes = $this->configuration->getProcesses();
                expect($this->runner->getWorkers())->to->have->length($processes);
            });
        });
    });
});