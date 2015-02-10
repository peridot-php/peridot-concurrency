<?php
use Peridot\Concurrency\Runner\StreamSelect\Message\Message;
use Peridot\Concurrency\Runner\StreamSelect\Message\MessageBroker;
use Peridot\Concurrency\Runner\StreamSelect\Message\StringPacker;

describe('Message', function () {
    beforeEach(function () {
        $this->resource = tmpfile();
        $this->message = new Message($this->resource);
    });

    describe('->receive()', function () {
        it('should read from a stream', function () {
            fwrite($this->resource, "hello world");
            fseek($this->resource, 0);
            $this->message->receive();
            expect($this->message->getContent())->to->equal('hello world');
        });

        it('should read until no more content', function () {
            $message = new Message($this->resource, 1);
            fwrite($this->resource, "hello world");
            fseek($this->resource, 0);
            $message->receive();
            expect($message->getContent())->to->equal('hello world');
        });

        it('should emit a data event when data is received', function() {
            $message = new Message($this->resource, 1);
            fwrite($this->resource, "hello world");
            fseek($this->resource, 0);
            $content = '';
            $message->on('data', function ($data) use (&$content) {
                $content .= $data;
            });

            $message->receive();
            expect($content)->to->equal('hello world');
        });

        it('should flag the message as readable', function () {
            $this->message->receive();
            expect($this->message->isReadable())->to->be->true;
        });

        it('should not allow reading from a writable message', function () {
            $this->message->write('hello');
            expect([$this->message, 'receive'])->to->throw('RuntimeException');
        });

        it('should emit an end event when a terminate string is read', function () {
            $message = null;
            $this->message->on('end', function ($msg) use (&$message) {
                $message = $msg;
            });
            fwrite($this->resource, 'hello world' . $this->message->getTerminateString());
            fseek($this->resource, 0);
            $this->message->receive();
            expect($message)->to->not->be->null;
        });
    });

    describe('->write()', function () {
        it('should write data to a stream', function () {
            $this->message->write('hello');
            $stream = $this->message->getResource();
            fseek($stream, 0);
            $content = fread($stream, 10);
            expect($content)->to->equal("hello\n");
        });

        it('should trime whitespace from message and terminate', function () {
            $this->message->write("hello       \n\n\n\n");
            $stream = $this->message->getResource();
            fseek($stream, 0);
            $content = fread($stream, 10);
            expect($content)->to->equal("hello\n");
        });

        it('should flag the message as writable', function () {
            $this->message->write('hello');
            expect($this->message->isWritable())->to->be->true;
        });

        it('should not allow writing to a readable stream', function() {
            $this->message->receive();
            expect([$this->message, 'write'])->with('hello')->to->throw('RuntimeException');
        });
    });

    describe('->end()', function () {
        it('should write data with a terminate signal', function () {
            $this->message->end('last');
            $stream = $this->message->getResource();
            fseek($stream , 0);
            $content = fread($stream, 128);
            expect($content)->to->equal("last\nTERMINATE\n");
        });

        it('should write only a terminate single if content is omitted', function () {
            $this->message->end();
            $stream = $this->message->getResource();
            fseek($stream , 0);
            $content = fread($stream, 128);
            expect($content)->to->equal("TERMINATE\n");
        });
    });

    describe('->getResource()', function() {
        it('should return the underlying resource', function () {
            $message = new Message($this->resource);
            expect($message->getResource())->to->equal($this->resource);
        });
    });

    describe('string packer accessors', function () {
        it('should allow access to the message string packer', function() {
            $packer = new StringPacker();
            $this->message->setStringPacker($packer);
            expect($this->message->getStringPacker())->to->equal($packer);
        });
    });

    describe('broker accessors', function () {
        it('should allow access to the message broker', function() {
            $broker = new MessageBroker();
            $this->message->setMessageBroker($broker);
            expect($this->message->getMessageBroker())->to->equal($broker);
        });
    });
});
