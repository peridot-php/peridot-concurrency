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

    context('when a peridot.concurrency.runner.end event is emitted', function () {
        it('should output a run time', function() {
            $this->emitter->emit('peridot.concurrency.runner.end', [0, []]);
            $output = $this->output->fetch();
            expect($output)->to->have->string('Run time: 0 ms');
        });

        it('should output error if present', function () {
            $this->emitter->emit('peridot.concurrency.runner.end', [['/path/to/spec.php' => 'FATAL ERROR']]);
            $output = $this->output->fetch();
            expect($output)->to->have->string('There was 1 error:');
            expect($output)->to->have->string('/path/to/spec.php');
            expect($output)->to->have->string('FATAL ERROR');
        });

        it('should output multiple errors if present', function () {
            $this->emitter->emit('peridot.concurrency.runner.end', [[
                '/path/to/spec.php' => 'FATAL ERROR',
                '/path/to/other.spec.php' => 'BAD ERROR'
            ]]);
            $output = $this->output->fetch();
            expect($output)->to->have->string('There were 2 errors:');
            expect($output)->to->have->string('/path/to/spec.php');
            expect($output)->to->have->string('FATAL ERROR');
            expect($output)->to->have->string('/path/to/other.spec.php');
            expect($output)->to->have->string('BAD ERROR');
        });

        it('should output nothing if there are no errors', function () {
            $this->emitter->emit('peridot.concurrency.runner.end', [[]]);
            $output = $this->output->fetch();
            expect($output)->to->not->match('/There were [\d]+ error/');
        });
    });

    context('when a runner.end event is emitted', function () {
        it('should output pass and failure counts', function () {
            $test1 = [['test' => new Test('description'), 'exception' => new Exception('failed')]];
            $this->reporter->writeTestReport($test1);
            $this->emitter->emit('runner.end', [0]);
            $output = $this->output->fetch();
            expect($output)->to->have->string('1 test failed, 0 tests passed');
        });

        it('should output pass only counts', function () {
            $test1 = [['test' => new Test('description'), 'exception' => null]];
            $this->reporter->writeTestReport($test1);
            $this->emitter->emit('runner.end', [0]);
            $output = $this->output->fetch();
            expect($output)->to->have->string('1 test passed');
        });

        it('should output test total', function () {
            $test1 = [['test' => new Test('description'), 'exception' => null]];
            $test2 = [['test' => new Test('description'), 'exception' => null]];
            $this->reporter->writeTestReport($test1);
            $this->reporter->writeTestReport($test2);
            $this->emitter->emit('runner.end', [0]);
            $output = $this->output->fetch();
            expect($output)->to->have->string('2 total');
        });
    });

    context('when a peridot.concurrency.stream-select.start event is emitted', function () {
        it('should output a count of processes', function () {
            $this->emitter->emit('peridot.concurrency.stream-select.start', [4]);
            $output = $this->output->fetch();
            expect($output)->to->have->string('Starting workers on 4 processes');
        });
    });

    describe('->writeTestReport()', function () {
        it('should increment the failure count if file contained failures', function () {
            $tests = [['test' => new Test('description'), 'exception' => new Exception('failed')]];
            $this->reporter->writeTestReport($tests);
            expect($this->reporter->getFailureCount())->to->equal(1);
        });

        it('should increment the success count if file contained no failures', function () {
            $tests = [['test' => new Test('description'), 'exception' => null]];
            $this->reporter->writeTestReport($tests);
            expect($this->reporter->getSuccessCount())->to->equal(1);
        });
    });

    describe('->writeTestHeader()', function () {
        beforeEach(function() {
            $this->path = '/path/to/test.php';
        });

        it('should strip extraneous slashes', function () {
            $path = '/path//to/test.php';
            $this->reporter->writeTestHeader($path, false);
            $header = $this->output->fetch();
            expect($header)->to->have->string('/path/to/test.php');
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

    describe('->onStreamSelectStart()', function () {
        it('should use a singluar string if process count is 1', function () {
            $this->reporter->onStreamSelectStart(1);
            $output = $this->output->fetch();
            expect($output)->to->have->string('Starting worker on 1 process');
        });
    });

    describe('->writeTestFailures()', function () {
        it('should output nothing for a passing array of tests', function () {
            $test = new Test('description');
            $this->reporter->writeTestFailures([['test' => $test, 'exception' => null]]);
            $content = $this->output->fetch();
            expect($content)->to->be->empty;
        });

        it('should output a failure if present', function () {
            $test = new Test('description');
            $exception = new Exception('failed');
            $entry = ['test' => $test, 'exception' => $exception];
            $this->reporter->writeTestFailures([$entry]);
            $content = $this->output->fetch();
            expect($content)->to->have->string(' 1)');
            expect($content)->to->have->string($exception->getMessage());

            $trace = preg_replace('/^#/m', "      #", $exception->getTraceAsString());
            expect($content)->to->have->string($trace);
        });
    });
});
