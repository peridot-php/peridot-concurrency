<?php
use Peridot\Concurrency\Runner\StreamSelect\Message\ErrorMessage;

describe('ErrorMessage', function () {
    context('when a data event is emitted', function () {
        beforeEach(function () {
            $this->resource = tmpfile();
        });

        afterEach(function () {
            fclose($this->resource);
        });

        it('should also emit an error event', function () {
            $message = new ErrorMessage($this->resource);
            $error = '';
            $message->on('error', function ($e) use (&$error) {
                $error = $e;
            });

            $message->emit('data', ['error']);
            expect($error)->to->equal('error');
        });
    });
});
