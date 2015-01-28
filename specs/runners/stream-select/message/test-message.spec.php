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
});
