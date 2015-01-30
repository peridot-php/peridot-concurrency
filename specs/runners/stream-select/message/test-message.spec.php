<?php
use Peridot\Concurrency\Runner\StreamSelect\Message\TestMessage;
use Peridot\Core\Suite;
use Peridot\Core\Test;

describe('TestMessage', function () {
    beforeEach(function () {
        $this->tmpfile = tmpfile();
        $this->message = new TestMessage($this->tmpfile);
    });

    describe('->write', function () {
        context('when writing a test', function () {
            it('should write test and status information', function () {
                $test = new Test('description');
                $this->message
                    ->setTest($test)
                    ->setStatus(TestMessage::TEST_PASS)
                    ->write();
                fseek($this->tmpfile, 0);

                $content = unserialize(fread($this->tmpfile, 4096));
                expect($content)->to->loosely->equal(['t',  null, 'description', 'description', 1, null, null, null]);
            });

            it('should write exception and event information', function () {
                $exception = null;
                try {
                    throw new Exception('failure');
                } catch (Exception $e) {
                    $exception = $e;
                }

                $this->message
                    ->setEvent('test.failed')
                    ->setException($exception)
                    ->write();

                fseek($this->tmpfile, 0);

                $content = unserialize(fread($this->tmpfile, 4096));
                expect($content)->to->loosely->equal([null, 'test.failed', null, null, null, $exception->getMessage(), $exception->getTraceAsString(), get_class($exception)]);
            });
        });
    });

    context('when receiving a data event', function () {
        context('and the data represents a test', function () {
            beforeEach(function () {
                $test = new Test('description');
                $this->message
                    ->setTest($test)
                    ->setEvent('test.passed')
                    ->setStatus(TestMessage::TEST_PASS)
                    ->write();
                fseek($this->tmpfile, 0);
                $this->content = fread($this->tmpfile, 4096);
            });

            it('should emit a test event when a complete passing test is received', function () {
                $test = null;
                $this->message->on('test.passed', function ($t) use (&$test) {
                    $test = $t;
                });
                $this->message->emit('data', [$this->content]);
                expect($test)->to->not->be->null->and->to->satisfy(function (Test $test) {
                    return $test->getDescription() === 'description';
                });
            });

            it('should emit a test event when multiple parts complete a test message', function () {
                $test = null;
                $this->message->on('test.passed', function ($t) use (&$test) {
                    $test = $t;
                });

                $part1 = substr($this->content, 0, 10);
                $part2 = substr($this->content, 10);

                $this->message->emit('data', [$part1]);
                $this->message->emit('data', [$part2]);

                expect($test)->to->not->be->null->and->to->satisfy(function (Test $test) {
                    return $test->getDescription() === 'description';
                });
            });

            it('should emit a test event even if the leading character is a new line followed by a complete message', function () {
                $test = null;
                $this->message->on('test.passed', function ($t) use (&$test) {
                    $test = $t;
                });
                $this->message->emit('data', ["\n" . $this->content]);
                expect($test)->to->not->be->null->and->to->satisfy(function (Test $test) {
                    return $test->getDescription() === 'description';
                });
            });

            it('should emit a test event even if the leading character is a new line followed by an incomplete message', function () {
                $test = null;
                $this->message->on('test.passed', function ($t) use (&$test) {
                    $test = $t;
                });

                $part1 = substr($this->content, 0, 10);
                $part2 = substr($this->content, 10);

                $this->message->emit('data', ["\n" . $part1]);
                $this->message->emit('data', [$part2]);

                expect($test)->to->not->be->null->and->to->satisfy(function (Test $test) {
                    return $test->getDescription() === 'description';
                });
            });
        });

        context('and the data represents a suite', function () {
            beforeEach(function () {
                $test = new Suite('description', function () {});
                $this->message
                    ->setTest($test)
                    ->setEvent('suite.started')
                    ->write();
                fseek($this->tmpfile, 0);
                $this->content = fread($this->tmpfile, 4096);
            });

            it('should emit a suite event when a message is received', function () {
                $suite = null;
                $this->message->on('suite.started', function ($s) use (&$suite) {
                    $suite = $s;
                });

                $this->message->emit('data', [$this->content]);

                expect($suite)->to->be->an->instanceof('Peridot\Core\Suite')->and->to->satisfy(function (Suite $suite) {
                    return $suite->getDescription() === 'description';
                });
            });
        });
    });
});
