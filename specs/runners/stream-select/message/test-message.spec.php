<?php
use Peridot\Concurrency\Runner\StreamSelect\Message\TestMessage;
use Peridot\Core\Suite;
use Peridot\Core\Test;

describe('TestMessage', function () {
    beforeEach(function () {
        $this->tmpfile = tmpfile();
        $this->message = new TestMessage($this->tmpfile);
    });

    describe('->writeTest()', function () {
        context('when writing a test', function () {
            it('should write a serialized test message containing type, description, status, and title', function () {
                $test = new Test('description');
                $this->message->writeTest($test, TestMessage::TEST_PASS);
                fseek($this->tmpfile, 0);

                $content = unserialize(fread($this->tmpfile, 4096));
                expect($content)->to->loosely->equal(['t', 'description', 1, 'description']);
            });

            it('should include an exception and test title when an exception is given', function () {
                $suite = new Suite('Parents', function () {});
                $test = new Test('should have children');
                $suite->addTest($test);
                $exception = new Exception('failure');
                $this->message->writeTest($test, TestMessage::TEST_FAIL, $exception);
                fseek($this->tmpfile, 0);

                $content = unserialize(fread($this->tmpfile, 4096));
                expect($content[4])->to->equal('failure');
                expect($content[5])->to->equal($exception->getTraceAsString());
                expect($content[6])->to->equal(get_class($exception));
            });
        });
    });
});
