<?php
use Evenement\EventEmitter;
use Peridot\Concurrency\Environment\Environment;
use Peridot\Concurrency\Runner\StreamSelect\Application\Application;
use Peridot\Concurrency\Runner\StreamSelect\Message\StringPacker;
use Peridot\Concurrency\Runner\StreamSelect\Message\TestMessage;
use Peridot\Concurrency\Runner\StreamSelect\Model\Suite;
use Peridot\Concurrency\Runner\StreamSelect\Model\Test;
use Peridot\Configuration;
use Prophecy\Argument;

require 'TestMessageReader.php';

describe('Application', function () {

    $this->peridotAddChildScope(new TestMessageReader);

    beforeEach(function () {
        $this->emitter = new EventEmitter();
        $this->readStream = tmpfile();
        $this->writeStream = tmpfile();
        $this->message = new TestMessage($this->writeStream);

        $environment = new Environment($this->emitter, $this->readStream, $this->writeStream);
        $config = new Configuration();
        $config->setDsl(__DIR__ . '/../../../../fixtures/environment/dsl.php');
        $config->setConfigurationFile(__DIR__ . '/../../../../fixtures/environment/peridot.php');
        $reader = $this->getProphet()->prophesize('Peridot\Concurrency\Environment\ReaderInterface');
        $reader->getConfiguration()->willReturn($config);
        $looper = $this->getProphet()->prophesize('Peridot\Concurrency\Runner\StreamSelect\Application\LooperInterface');
        $looper->loop(Argument::type('Peridot\Runner\Context'), $environment, $this->message)->shouldBeCalled();

        $this->application = new Application($environment, $reader->reveal(), $looper->reveal());

        /**
         * An application does not listen until run.
         */
        $this->application->run($this->message);
    });

    afterEach(function () {
        fclose($this->readStream);
        fclose($this->writeStream);
        $this->getProphet()->checkPredictions();
    });

    context('when a suite.start event is emitted', function () {
        it('should write a suite.start event to the test message', function () {
            $suite = new Suite('description');
            $suite->setTitle('my title');
            $this->emitter->emit('suite.start', [$suite]);

            $this->expectMessageValues($this->writeStream, [
                's',
                'suite.start',
                'description',
                'my title'
            ]);
        });

        it('should write nothing if the suite has no description', function () {
            $suite = new Suite('');
            $this->emitter->emit('suite.start', [$suite]);
            $content = $this->readMessage($this->writeStream);
            expect($content)->to->be->empty;
        });
    });

    context('when a suite.end event is emitted', function () {
        it('should write a suite.end event to the test message', function () {
            $suite = new Suite('description');
            $suite->setTitle('my title');
            $this->emitter->emit('suite.end', [$suite]);

            $this->expectMessageValues($this->writeStream, [
                's',
                'suite.end',
                'description',
                'my title'
            ]);
        });

        it('should write nothing if the suite has no description', function () {
            $suite = new Suite('');
            $this->emitter->emit('suite.end', [$suite]);
            $content = $this->readMessage($this->writeStream);
            expect($content)->to->be->empty;
        });
    });

    context('when a test.passed event is emitted', function () {
        it('should write a test.passed event to the test message', function () {
            $test = new Test('description');
            $test->setTitle('my title');
            $this->emitter->emit('test.passed', [$test]);

            $this->expectMessageValues($this->writeStream, [
                't',
                'test.passed',
                TestMessage::TEST_PASS
            ]);
        });
    });

    context('when a test.failed event is emitted', function () {
        it('should write a test.failed event to the test message', function () {
            $test = new Test('description');
            $test->setTitle('my title');
            $exception = new Exception('message');
            $this->emitter->emit('test.failed', [$test, $exception]);
            $packer = new StringPacker();

            $this->expectMessageValues($this->writeStream, [
                't',
                'test.failed',
                TestMessage::TEST_FAIL,
                $exception->getMessage(),
                $packer->packString($exception->getTraceAsString()),
                get_class($exception)
            ]);
        });
    });

    context('when a test.pending event is emitted', function () {
        it('should write a test.pending event to the test message', function () {
            $test = new Test('description');
            $test->setTitle('my title');
            $this->emitter->emit('test.pending', [$test]);

            $this->expectMessageValues($this->writeStream, [
                't',
                'test.pending',
                TestMessage::TEST_PENDING
            ]);
        });
    });

    context('when a suite.halt event is emitted', function () {
        it('should write a suite.halt event to the test message', function () {
            $this->emitter->emit('suite.halt');

            $this->expectMessageValues($this->writeStream, [
                'suite.halt'
            ]);
        });
    });
});
