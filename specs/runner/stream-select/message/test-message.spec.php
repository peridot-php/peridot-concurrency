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
                $test->setFile(__FILE__);
                $this->message
                    ->setTest($test)
                    ->setStatus(TestMessage::TEST_PASS)
                    ->write();
                fseek($this->tmpfile, 0);

                $content = json_decode(fread($this->tmpfile, 8192));
                expect($content)->to->loosely->equal(['t',  null, 'description', 'description', 1, __FILE__,  null, null, null]);
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

                $read = trim(fread($this->tmpfile, 8192));
                $content = json_decode($read);
                $packer = $this->message->getStringPacker();
                expect($content[6])->to->not->be->null('message should not be null');
                expect($content[7])->to->equal($packer->packString($exception->getTraceAsString()));
                expect($content[8])->to->not->be->null('type should not be null');
            });
        });
    });

    context('when receiving a data event', function () {
        context('and the data represents a test', function () {
            beforeEach(function () {
                $test = new Test('description');
                $test->setFile(__FILE__);
                $this->message
                    ->setTest($test)
                    ->setEvent('test.passed')
                    ->setStatus(TestMessage::TEST_PASS)
                    ->write();
                fseek($this->tmpfile, 0);
                $this->content = fread($this->tmpfile, 8192);
                $this->offset = ftell($this->tmpfile);
            });

            it('should emit a test event when a complete passing test is received', function () {
                $test = null;
                $this->message->on('test.passed', function ($t) use (&$test) {
                    $test = $t;
                });
                $this->message->emit('data', [$this->content]);
                expect($test)->to->not->be->null->and->to->satisfy(function (Test $test) {
                    return $test->getDescription() === 'description' && $test->getFile() === __FILE__;
                });
            });

            it('should clear the buffer when the end of a message is received', function () {
                $this->message->emit('data', ["some content"]);
                $buffer = $this->message->getBuffer();
                expect($buffer)->to->not->be->empty;
                $this->message->emit('end');
                $buffer = $this->message->getBuffer();
                expect($buffer)->to->be->empty;
            });

            it('should not try to serialize the termination string', function () {
                $this->message->end();
                fseek($this->tmpfile, $this->offset);
                $newContent = fread($this->tmpfile, 4096);
                $this->message->emit('data', [$newContent]);
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

            it('should emit an error and clear the buffer if an unrecognized format is given', function () {
                $message = "booooooom";
                $error = null;
                $reference = null;

                $this->message->on('error', function ($e, $m) use (&$error, &$reference) {
                    $error = $e;
                    $reference = $m;
                });

                $this->message->emit('data', [$message . "\n"]);

                expect($error)->to->have->string($message);
                expect($reference)->to->equal($this->message);
                expect($this->message->getBuffer())->to->be->empty;
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

            it('should emit test event with unpacked data', function () {
                $test = null;
                $data = [];
                $this->message->on('test.passed', function ($t, $d) use (&$test, &$data) {
                    $test = $t;
                    $data = $d;
                });
                $this->message->emit('data', [$this->content]);
                expect($data)->to->have->length->of->at->least(1);
            });

            it('should emit exception and title data with event', function () {
                $suite = new Suite("Parents", function () {});
                $test = new Test('should have children');
                $suite->addTest($test);
                $exception = new RuntimeException('a failure');
                $tmpfile = tmpfile();
                $message = new TestMessage($tmpfile);
                $message
                    ->setTest($test)
                    ->setEvent('test.failed')
                    ->setException($exception)
                    ->write();
                fseek($tmpfile, 0);
                $content = fread($tmpfile, 8192);

                $receivedTest = null;
                $receivedException = null;

                $message->on('test.failed', function ($t, $e) use (&$receivedTest, &$receivedException) {
                    $receivedTest = $t;
                    $receivedException = $e;
                });

                $message->emit('data', [$content]);

                expect($receivedTest->getDescription())->to->equal('should have children');
                expect($receivedTest->getTitle())->to->equal('Parents should have children');
                expect($receivedException->getMessage())->to->equal($exception->getMessage());
                expect($receivedException->getTraceAsString())->to->equal($exception->getTraceAsString());
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
