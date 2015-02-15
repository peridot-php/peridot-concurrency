<?php
use Peridot\Concurrency\Runner\StreamSelect\Application\RunnerLooper;

describe('RunnerLooper', function () {
    beforeEach(function () {
        $this->looper = new RunnerLooper();
    });

    describe('->getTestInfo()', function () {
        it('should parse input into a test path and a token', function () {
            $input = '/path/to/test.spec.php:mytoken';
            list($token, $path) = $this->looper->getTestInfo($input);
            expect($token)->to->equal('mytoken');
            expect($path)->to->equal('/path/to/test.spec.php');
        });

        context('when path includes separator', function () {
            it('should parse input into a test path and token', function () {
                $input = '/path/to/test:spec:php:mytoken';
                list($token, $path) = $this->looper->getTestInfo($input);
                expect($token)->to->equal('mytoken');
                expect($path)->to->equal('/path/to/test:spec:php');
            });
        });
    });
});
