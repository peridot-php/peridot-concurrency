<?php
use Evenement\EventEmitter;
use Peridot\Concurrency\Runner\StreamSelect\IO\TmpfileOpen;
use Peridot\Concurrency\Runner\StreamSelect\IO\Worker;

describe('Worker', function () {

    beforeEach(function () {
        $this->emitter = new EventEmitter();
        $this->worker = new Worker('/path/to/bin', $this->emitter, new TmpfileOpen());
    });

    describe('->run()', function () {
        it('should emit a peridot.concurrency.worker.run event', function () {
            $self = null;
            $this->emitter->on('peridot.concurrency.worker.run', function ($worker) use (&$self) {
                $self = $worker;
            });
            $this->worker->run('/path/to/test.php');
            expect($self)->to->equal($this->worker);
        });
    });

    describe('->hasStream()', function () {
        it('should return true if the worker has the given stream', function () {
            $input = $this->worker->getInputStream();
            $output = $this->worker->getOutputStream();
            $err = $this->worker->getErrorStream();

            expect($this->worker->hasStream($input))->to->be->true;
            expect($this->worker->hasStream($output))->to->be->true;
            expect($this->worker->hasStream($err))->to->be->true;
        });
    });
});
