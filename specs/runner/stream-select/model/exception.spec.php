<?php
describe('Exception', function () {
    describe('type accessors', function () {
        it('should allow access to the exception type', function () {
            $exception = new \Peridot\Concurrency\Runner\StreamSelect\Model\Exception();
            $exception->setType('RuntimeException');
            expect($exception->getType())->to->equal('RuntimeException');
        });
    });
});
