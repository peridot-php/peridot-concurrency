<?php
use Peridot\Concurrency\Runner\StreamSelect\Model\Suite;

describe('Suite', function () {
    describe('title accessors', function () {
        it('should allow access to the suite title', function () {
            $suite = new Suite('description');
            $suite->setTitle('hello');
            expect($suite->getTitle())->to->equal('hello');
        });
    });
});
