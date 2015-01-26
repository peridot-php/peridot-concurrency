<?php
use Peridot\Concurrency\Runner\StreamSelect\Message\ReadableMessage;

describe('ReadableMessage', function () {
    beforeEach(function () {
        $this->resource = tmpfile();
    });

    describe('->receive()', function () {
        it('should read from a stream', function () {
            $message = new ReadableMessage($this->resource);
            fwrite($this->resource, "hello world");
            $message->receive();
            expect($message->getContent())->to->equal('hello world');
        });

        it('should read until no more content', function () {
            $message = new ReadableMessage($this->resource, 1);
            fwrite($this->resource, "hello world");
            $message->receive();
            expect($message->getContent())->to->equal('hello world');
        });
    });

    describe('->getResource()', function() {
        it('should return the underlying resource', function () {
            $message = new ReadableMessage($this->resource);
            expect($message->getResource())->to->equal($this->resource);
        });
    });
});
