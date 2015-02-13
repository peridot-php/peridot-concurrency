<?php
use Peridot\Concurrency\Runner\StreamSelect\IO\Worker;
use Peridot\Concurrency\Runner\StreamSelect\IO\WorkerInterface;
use Peridot\Concurrency\Runner\StreamSelect\IO\WorkerPool;
use Peridot\Concurrency\Runner\StreamSelect\IO\TmpfileOpen;
use Peridot\Concurrency\Configuration;
use Peridot\Concurrency\Runner\StreamSelect\Message\Message;
use Peridot\Concurrency\Runner\StreamSelect\Message\MessageBroker;
use Peridot\Concurrency\Test\TestBroker;
use Peridot\Configuration as CoreConfig;
use Evenement\EventEmitter;

describe('WorkerPool', function () {
    beforeEach(function () {
        $core = new CoreConfig();
        $this->configuration = new Configuration($core);
        $this->configuration->setProcesses(2);
        $this->emitter = new EventEmitter();
        $this->broker = new MessageBroker();

        $open = new TmpfileOpen();
        $this->pool = new WorkerPool($this->configuration, $this->emitter, $this->broker, $open);
    });

    beforeEach(function () {
        $interface = 'Peridot\Concurrency\Runner\StreamSelect\IO\WorkerInterface';
        $this->workers = [];
        for ($i = 0; $i <= $this->configuration->getProcesses(); $i++) {
            $this->workers[] = $this->getProphet()->prophesize($interface);
        }
    });

    context('when peridot.concurrency.load event is emitted', function () {
        it('should set pending test on the pool', function () {
            $this->emitter->emit('peridot.concurrency.load', [['one.spec.php', 'two.spec.php', 'three.spec.php']]);
            expect($this->pool->getPending())->to->have->length(3);
        });
    });

    context('when peridot.concurrency.worker.run event is emitted', function () {
        it('should increment the running count', function () {
            $this->emitter->emit('peridot.concurrency.worker.run', [$this->workers[0]->reveal()]);
            expect($this->pool->getRunning())->to->have->length(1);
        });
    });

    context('when the broker emits an error event', function () {
        beforeEach(function () {
            $this->worker = new Worker('/path/to/nowhere', $this->emitter, new TmpfileOpen());
            $this->pool->attach($this->worker);
            $this->messages = $this->broker->getMessages();
            $this->messages->rewind();
            $this->message = $this->messages->current();
        });

        it('should replace the worker and the message', function () {
            $this->broker->emit('error', ['catastrophe!', $this->message]);
            $workers = $this->pool->getWorkers();
            $messages = $this->broker->getMessages();
            $messages->rewind();
            $workers->rewind();
            expect($workers->current())->not->to->equal($workers);
            expect($messages->current())->not->to->equal($this->message);
        });

        it('should emit a peridot.concurrency.worker.error event', function () {
            $worker = null;
            $error = null;
            $this->emitter->on('peridot.concurrency.worker.error', function ($e, $w) use (&$error, &$worker) {
                $error = $e;
                $worker = $w;
            });
            $this->broker->emit('error', ['catastrophe!', $this->message]);
            expect($error)->to->equal('catastrophe!');
            expect($worker)->to->equal($this->worker);
        });
    });

    describe('->getMessageBroker()', function () {
        it("it should return the pool's message broker", function () {
            expect($this->pool->getMessageBroker())->to->equal($this->broker);
        });
    });

    /**
     * Helper for mocking stream accessors.
     *
     * @param $worker
     */
    $mockStreams = function ($worker) {
        $worker->getOutputStream()->willReturn(tmpfile());
        $worker->getErrorStream()->willReturn(tmpfile());
    };

    describe('->attach()', function () use ($mockStreams) {

        afterEach(function () {
            $this->getProphet()->checkPredictions();
        });


        it('should start the attached worker', function () use ($mockStreams) {
            $this->pool->attach($this->workers[0]->reveal());
            $mockStreams($this->workers[0]);
            $this->workers[0]->start()->shouldBeCalled();
        });

        it('should not start the worker if it is already started', function () use ($mockStreams) {
            $mockStreams($this->workers[1]);
            $this->workers[1]->isStarted()->willReturn(true);
            $this->pool->attach($this->workers[1]->reveal());
            $this->workers[1]->start()->shouldNotBeCalled();
        });

        it('should return true when a worker is attached', function () {
            $attached = $this->pool->attach($this->workers[0]->reveal());
            expect($attached)->to->be->true;
        });

        it('should return false when attempting to attach a worker beyond process count', function () {
            $processes = $this->configuration->getProcesses();
            for ($i = 0; $i < $processes; $i++) {
                $attached = $this->pool->attach($this->workers[$i]->reveal());
                expect($attached)->to->be->true;
            }
            $attached = $this->pool->attach($this->workers[$processes]);
            expect($attached)->to->be->false();
        });
    });

    describe('->detach()', function () use ($mockStreams) {

        afterEach(function () {
            $this->getProphet()->checkPredictions();
        });

        it('should free the detached worker', function () {
            $worker = $this->workers[0]->reveal();
            $this->pool->attach($worker);
            $this->pool->detach($worker);
            $this->workers[0]->free()->shouldBeCalled();
        });

        it('should remove the detached worker', function () {
            $worker = $this->workers[0]->reveal();
            $this->pool->attach($worker);
            $this->pool->detach($worker);
            expect($this->pool->getWorkers()->count())->to->equal(0);
        });
    });

    describe('->start()', function () {
        beforeEach(function() {
            $open = new TmpfileOpen();
            $broker = new TestBroker($this->emitter);
            $this->pool = new WorkerPool($this->configuration, $this->emitter, $broker, $open);
            $broker->setPool($this->pool);
        });

        it('should start workers', function () {
            $this->pool->setPending(['/path/to/thing']);
            $this->pool->start('command');
            $processes = $this->configuration->getProcesses();
            expect($this->pool->getWorkers())->to->have->length($processes);
        });
    });

    describe('->startWorkers()', function () {
        it('should attach workers for the number of processes', function () {
            $this->pool->startWorkers();
            $processes = $this->configuration->getProcesses();
            expect($this->pool->getWorkers())->to->have->length($processes);
        });

        it('should store read streams for all workers', function () {
            $this->pool->startWorkers();
            $workers = $this->pool->getWorkers();
            $readStreams = [];
            foreach ($workers as $worker) {
                $readStreams[] = $worker->getOutputStream();
            }
            expect($this->pool->getReadStreams())->to->loosely->equal($readStreams);;
        });

        it('should emit a peridot.concurrency.pool.start-workers event', function () {
            $self = null;
            $this->emitter->on('peridot.concurrency.pool.start-workers', function ($pool) use (&$self) {
                $self = $pool;
            });
            $this->pool->startWorkers();
            expect($self)->to->equal($this->pool);
        });

        context('when workers are already attached', function () {
            it('should not add additional workers', function () {
                $interface = 'Peridot\Concurrency\Runner\StreamSelect\IO\WorkerInterface';
                $worker = $this->getProphet()->prophesize($interface);
                $this->pool->attach($worker->reveal());
                $this->pool->startWorkers();
                $processes = $this->configuration->getProcesses();
                expect($this->pool->getWorkers())->to->have->length($processes);
            });
        });
    });

    describe('->getAvailableWorker()', function () use ($mockStreams) {

        beforeEach(function () use ($mockStreams) {
            for ($i = 0; $i < $this->configuration->getProcesses(); $i++) {
                $worker = $this->workers[$i];
                $worker->isRunning()->willReturn(true);
                $worker->isStarted()->willReturn(false);
                $worker->start()->shouldBeCalled();
                $mockStreams($worker);
                $this->pool->attach($worker->reveal());
            }
        });

        afterEach(function () {
            $this->getProphet()->checkPredictions();
        });

        it('should get the first worker that is not running', function () {
            $stoppedWorker = $this->workers[$this->configuration->getProcesses() - 1];
            $stoppedWorker->isRunning()->willReturn(false);

            expect($this->pool->getAvailableWorker())->to->equal($stoppedWorker->reveal());
        });

        it('should return null if all workers are unavailable', function () {
            expect($this->pool->getAvailableWorker())->to->be->null;
        });
    });

    describe('->isWorking()', function () {
        it('should return false if no pending tests and no running', function () {
            expect($this->pool->isWorking())->to->be->false;
        });

        it('should return true if there are running workers and no pending tests', function () {
            $worker = new Worker('bin', $this->emitter, new TmpfileOpen());
            $this->pool->attach($worker);
            $worker->run('some path');
            expect($this->pool->isWorking())->to->be->true;
        });

        it('should return true if there are not running workers and some pending tests', function () {
            $this->pool->setPending(['spec.php']);
            expect($this->pool->isWorking())->to->be->true;
        });
    });

    context('when a message end event is emitted on the message broker', function () {
        beforeEach(function () {
            $this->worker = new Worker('bin', $this->emitter, new TmpfileOpen());
            $this->pool->attach($this->worker);
            $message = new Message($this->worker->getOutputStream());
            $this->broker->addMessage($message);

            $theWorker = null;
            $this->emitter->on('peridot.concurrency.worker.completed', function ($w) use (&$theWorker) {
                $theWorker = $w;
            });
            $this->worker->run('some path');
            $message->emit('end', [$message]);
            $this->completed = $theWorker;
        });

        it('should emit a peridot.concurrency.worker.completed event for the worker that has the message resource', function () {
            expect($this->completed)->to->equal($this->worker);
        });

        it('should set the total job time elapsed', function () {
            $info = $this->completed->getJobInfo();
            expect($info->end)->to->not->be->null;
        });
    });

    context('when peridot.concurrency.worker.completed event is emitted', function () {
        afterEach(function () {
            $this->getProphet()->checkPredictions();
        });

        it('should free the completed worker', function () {
            $worker = $this->workers[0];
            $worker->free()->shouldBeCalled();
            $this->emitter->emit('peridot.concurrency.worker.completed', [$worker->reveal()]);
        });

        it('should reduce the running workers', function () {
            $worker = new Worker('bin', $this->emitter, new TmpfileOpen());
            $this->pool->attach($worker);
            $worker->run('some path');
            $this->emitter->emit('peridot.concurrency.worker.completed', [$worker]);
            expect($this->pool->getRunning())->to->have->length(0);
        });
    });
});
