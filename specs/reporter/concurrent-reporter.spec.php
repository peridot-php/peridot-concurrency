<?php
use Evenement\EventEmitter;
use Peridot\Concurrency\Reporter\ConcurrentReporter;
use Peridot\Concurrency\Runner\StreamSelect\IO\TmpfileOpen;
use Peridot\Concurrency\Runner\StreamSelect\IO\Worker;
use Peridot\Configuration;
use Peridot\Core\Suite;
use Peridot\Core\Test;
use Symfony\Component\Console\Output\BufferedOutput;

describe('ConcurrentReporter', function () {
    beforeEach(function () {
        $configuration = new Configuration();
        $this->output = new BufferedOutput();
        $this->emitter = new EventEmitter();
        $this->reporter = new ConcurrentReporter($configuration, $this->output, $this->emitter);
    });

    beforeEach(function () {
        $suite = new Suite('description', function () {});
        $suite->setFile(__FILE__);
        $this->emitter->emit('suite.start', [$suite]);

        $this->test = new Test('description');
        $this->test->setFile(__FILE__);
        $this->exception = new \Peridot\Concurrency\Runner\StreamSelect\Model\Exception();
    });

    context('when the suite.start event is emitted', function () {
        it('should track the suite', function () {
            $suites = $this->reporter->getSuites();
            expect($suites)->to->have->property(__FILE__);
        });
    });

    context('when the test.passed event is emitted', function () {
        beforeEach(function () {
            $this->emitter->emit('test.passed', [$this->test]);
        });

        it('should be set the test property of the entry', function () {
            expect($this->reporter->getSuites())->to->have->deep->property('[' . __FILE__ . '][0][test]', $this->test);
        });

        it('should have a null value for an exception', function () {
            expect($this->reporter->getSuites())->to->have->deep->property('[' . __FILE__ . '][0][exception]', null);
        });
    });

    context('when the test.pending event is emitted', function () {
        beforeEach(function () {
            $this->emitter->emit('test.pending', [$this->test]);
        });

        it('should set the test and pending status to true for the entry', function () {
            $entry = $this->reporter->getSuites()[__FILE__][0];
            expect($entry['test'])->to->equal($this->test);
        });

        it('should have a null value for an exception', function () {
            expect($this->reporter->getSuites())->to->have->deep->property('[' . __FILE__ . '][0][exception]', null);
        });
    });

    context('when test.failed event is emitted', function () {
        beforeEach(function () {
            $this->emitter->emit('test.failed', [$this->test, $this->exception]);
        });

        it('should store the test and exception on the suite entry', function () {
            $suites = $this->reporter->getSuites();
            $entry = $suites[__FILE__][0];
            expect($entry['test'])->to->equal($this->test);
            expect($entry['exception'])->to->equal($this->exception);
        });
    });

    context('when a peridot.concurrency.worker.completed event is emitted', function () {
        it('should associated elapsed time from the worker', function () {
            $worker = new Worker('/path/to/executable.php', $this->emitter, new TmpfileOpen());
            $worker->run(__FILE__);
            $worker->getJobInfo()->end = microtime(true);
            $this->emitter->emit('test.passed', [$this->test]);
            $this->emitter->emit('peridot.concurrency.worker.completed', [$worker]);

            $info = $worker->getJobInfo();
            $time = $this->reporter->getTimeFor(__FILE__);

            expect($time)->to->equal($info->end - $info->start);
        });
    });

    describe('->writeTestHeader()', function () {
        beforeEach(function() {
            $this->path = '/path/to/test.php';
        });

        context('when the test path is passing', function () {
            it('should write a passing message message', function () {
                $this->reporter->writeTestHeader($this->path, false);
                $header = $this->output->fetch();
                expect($header)->to->have->string('PASS');
                expect($header)->to->have->string($this->path);
                expect($header)->to->match('/\([0-9]+(.[0-9]+)? m?s\)/');
            });
        });

        context('when the test path is failing', function () {
            it('should write a passing message message', function () {
                $this->reporter->writeTestHeader($this->path, true);
                $header = $this->output->fetch();
                expect($header)->to->have->string('FAIL');
                expect($header)->to->have->string($this->path);
                expect($header)->to->match('/\([0-9]+(.[0-9]+)? m?s\)/');
            });
        });
    });
});
