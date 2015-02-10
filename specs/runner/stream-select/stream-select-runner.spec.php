<?php
use Evenement\EventEmitter;
use Peridot\Concurrency\Runner\StreamSelect\StreamSelectRunner;
use Peridot\Concurrency\Test\TestBroker;
use Peridot\Core\Suite;
use Peridot\Core\Test;
use Peridot\Core\TestResult;
use Prophecy\Argument;

describe('StreamSelectRunner', function () {
    beforeEach(function () {
        $this->pool = $this->getProphet()->prophesize('Peridot\Concurrency\Runner\StreamSelect\IO\WorkerPoolInterface');
        $this->emitter = new EventEmitter();
        $this->broker = new TestBroker($this->emitter);
        $this->pool->getMessageBroker()->willReturn($this->broker);
        $this->runner = new StreamSelectRunner($this->emitter, $this->pool->reveal());
    });

    describe('->run()', function () {
        beforeEach(function () {
            $this->pool->start(Argument::any())->shouldBeCalled();
        });

        afterEach(function () {
            $this->getProphet()->checkPredictions();
        });

        it('should emit a peridot.concurrency.runner.end event with time and errors', function () {
            $result = new TestResult($this->emitter);
            $time = null;
            $errors = null;

            $this->emitter->on('peridot.concurrency.runner.end', function ($t, $e) use (&$time, &$errors) {
                $time = $t;
                $errors = $e;
            });

            $this->runner->run($result);
            expect($time)->satisfy('is_float', 'time should be a float');
            expect($errors)->to->be->an('array');
        });
    });

    context('when a suite.start event is emitted on the broker', function () {
        it('should emit a suite.start event', function () {
            $suite = null;
            $this->emitter->on('suite.start', function ($s) use (&$suite) {
                $suite = $s;
            });
            $this->broker->emit('suite.start', [new Suite('description', function() {})]);
            expect($suite)->to->not->be->null;
        });
    });

    context('when a suite.end event is emitted on the broker', function () {
        it('should emit a suite.end event', function () {
            $suite = null;
            $this->emitter->on('suite.end', function ($s) use (&$suite) {
                $suite = $s;
            });
            $this->broker->emit('suite.end', [new Suite('description', function() {})]);
            expect($suite)->to->not->be->null;
        });
    });

    context('when a test.passed event is emitted on the broker', function () {
        it('should emit a test.passed event', function () {
            $suite = null;
            $this->emitter->on('test.passed', function ($s) use (&$suite) {
                $suite = $s;
            });
            $this->broker->emit('test.passed', [new Test('description')]);
            expect($suite)->to->not->be->null;
        });
    });

    context('when a test.failed event is emitted on the broker', function () {
        it('should emit a test.failed event', function () {
            $suite = null;
            $error = null;
            $this->emitter->on('test.failed', function ($s, $e) use (&$suite, &$error) {
                $suite = $s;
                $error = $e;
            });
            $this->broker->emit('test.failed', [new Test('description'), new Exception('error')]);
            expect($suite)->to->not->be->null;
            expect($error)->to->not->be->null;
        });
    });

    context('when a test.pending event is emitted on the broker', function () {
        it('should emit a test.pending event', function () {
            $suite = null;
            $this->emitter->on('test.pending', function ($s) use (&$suite) {
                $suite = $s;
            });
            $this->broker->emit('test.pending', [new Test('description')]);
            expect($suite)->to->not->be->null;
        });
    });

    context('when an error event is emitted on the broker', function () {
        it('should store the error', function () {
            $this->broker->emit('error', ['error!']);
            $errors = $this->runner->getErrors();
            expect($errors)->to->contain('error!');
        });
    });
});
