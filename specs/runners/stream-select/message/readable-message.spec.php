<?php
use Peridot\Concurrency\Runner\StreamSelect\Message\Message;

describe('Message', function () {
    beforeEach(function () {
        $this->resource = tmpfile();
        $this->message = new Message($this->resource);
    });

    describe('->receive()', function () {
        it('should read from a stream', function () {
            fwrite($this->resource, "hello world");
            $this->message->receive();
            expect($this->message->getContent())->to->equal('hello world');
        });

        it('should read until no more content', function () {
            $message = new Message($this->resource, 1);
            fwrite($this->resource, "hello world");
            $message->receive();
            expect($message->getContent())->to->equal('hello world');
        });

        it('should emit a data event when data is received', function() {
            $message = new Message($this->resource, 1);
            fwrite($this->resource, "hello world");
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

    describe('->getResource()', function() {
        it('should return the underlying resource', function () {
            $message = new Message($this->resource);
            expect($message->getResource())->to->equal($this->resource);
        });
    });
});
