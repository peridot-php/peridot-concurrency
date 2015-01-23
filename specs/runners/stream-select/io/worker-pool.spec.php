<?php
use Peridot\Concurrency\Runner\StreamSelect\IO\WorkerPool;
use Peridot\Concurrency\Runner\StreamSelect\IO\TmpfileOpen;
use Peridot\Concurrency\Configuration;
use Peridot\Configuration as CoreConfig;
use Evenement\EventEmitter;

describe('WorkerPool', function () {
    beforeEach(function () {
        $core = new CoreConfig();
        $this->configuration = new Configuration($core);
        $this->configuration->setProcesses(2);
        $this->emitter = new EventEmitter();

        $open = new TmpfileOpen();
        $this->pool = new WorkerPool($this->configuration, $this->emitter, $open);
    });

    beforeEach(function () {
        $interface = 'Peridot\Concurrency\Runner\StreamSelect\IO\WorkerInterface';
        $this->workers = [];
        for ($i = 0; $i <= $this->configuration->getProcesses(); $i++) {
            $this->workers[] = $this->getProphet()->prophesize($interface);
        }
    });

    context('when peridot.concurrency.loadstart event is emitted', function () {
        it('should set pending count on the pool', function () {
            $this->emitter->emit('peridot.concurrency.loadstart', [3]);
            expect($this->pool->getPending())->to->equal(3);
        });
    });

    describe('->attach()', function () {

        afterEach(function () {
            $this->getProphet()->checkPredictions();
        });

        it('should start the attached worker', function () {
            $this->pool->attach($this->workers[0]->reveal());
            $this->workers[0]->start()->shouldBeCalled();
        });

        it('should not start the worker if it is already started', function () {
            $this->workers[1]->isStarted()->willReturn(true);
            $this->pool->attach($this->workers[1]->reveal());
            $this->workers[1]->start()->shouldNotBeCalled();
        });

        it('should return true when a worker is attached', function () {
            $attached = $this->pool->attach($this->workers[0]->reveal());
            expect($attached)->to->be->true;
        });

        it('should return false when attempting to attach a worker beyond process count', function () {
            $processes = $this->configuration->getProcesses();
            for ($i = 0; $i < $processes; $i++) {
                $attached = $this->pool->attach($this->workers[$i]->reveal());
                expect($attached)->to->be->true;
            }
            $attached = $this->pool->attach($this->workers[$processes]);
            expect($attached)->to->be->false();
        });
    });

    describe('->startWorkers()', function () {
        it('should attach workers for the number of processes', function () {
            $this->pool->startWorkers();
            $processes = $this->configuration->getProcesses();
            expect($this->pool->getWorkers())->to->have->length($processes);
        });

        context('when workers are already attached', function () {
            it('should not add additional workers', function () {
                $interface = 'Peridot\Concurrency\Runner\StreamSelect\IO\WorkerInterface';
                $worker = $this->getProphet()->prophesize($interface);
                $this->pool->attach($worker->reveal());
                $this->pool->startWorkers();
                $processes = $this->configuration->getProcesses();
                expect($this->pool->getWorkers())->to->have->length($processes);
            });
        });
    });

    describe('->getAvailableWorker()', function () {

        afterEach(function () {
            $this->getProphet()->checkPredictions();
        });

        it('should get the first worker that is not running', function () {

            for ($i = 0; $i < $this->configuration->getProcesses(); $i++) {
                $worker = $this->workers[$i];
                $worker->isRunning()->willReturn(true);
                $worker->isStarted()->willReturn(false);
                $worker->start()->shouldBeCalled();
                $this->pool->attach($worker->reveal());
            }

            $stoppedWorker = $this->workers[$this->configuration->getProcesses() - 1];
            $stoppedWorker->isRunning()->willReturn(false);

            expect($this->pool->getAvailableWorker())->to->equal($stoppedWorker->reveal());
        });
    });
});
