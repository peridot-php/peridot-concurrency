<?php
use Evenement\EventEmitter;
use Peridot\Concurrency\Runner\StreamSelect\IO\TmpfileOpen;
use Peridot\Concurrency\Runner\StreamSelect\IO\Worker;

describe('Worker', function () {

    beforeEach(function () {
        $this->emitter = new EventEmitter();
        $this->worker = new Worker('/path/to/bin', $this->emitter, new TmpfileOpen());
    });

    describe('->getId()', function () {
        it('should always return the same id', function () {
            $id = $this->worker->getId();
            expect($this->worker->getId())->to->equal($id);
        });

        it('should return unique ids', function () {
            $other = new Worker('/path/to/bin', $this->emitter, new TmpfileOpen());
            expect($other->getId())->to->not->equal($this->worker->getId());
        });
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

        it('should run the worker', function () {
            $this->worker->run('/path/to/test.php');
            expect($this->worker->isRunning())->to->be->true;
        });

        it('should set job path and start time on the worker', function () {
            $this->worker->run('/path/to/test.php');
            $job = $this->worker->getJobInfo();
            expect($job->file)->to->equal('/path/to/test.php');
            expect($job->start)->to->not->be->null;
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

    describe('->close()', function () {
        beforeEach(function () {
            $this->worker->start();
        });

        it('should close related resources', function () {
            $this->worker->close();

            $input = $this->worker->getInputStream();
            $output = $this->worker->getOutputStream();
            $error = $this->worker->getErrorStream();
            $process = $this->worker->getProcess();

            expect($input)->to->not->satisfy('is_resource');
            expect($output)->to->not->satisfy('is_resource');
            expect($error)->to->not->satisfy('is_resource');
            expect($process)->to->not->satisfy('is_resource');
        });

        it('should stop a running worker', function () {
            $this->worker->run('/path/to/test.php');
            $this->worker->close();
            expect($this->worker->isRunning())->to->be->false;
            expect($this->worker->isStarted())->to->be->false;
        });
    });

    describe('->free()', function () {
        it('should make the worker stop running', function () {
            $this->worker->run('/path/to/test.php');
            $this->worker->free();
            expect($this->worker->isRunning())->to->be->false;
        });
    });
});
