<?php
use Peridot\Concurrency\Runner\StreamSelect\Message\Message;
use Peridot\Concurrency\Runner\StreamSelect\Message\MessageBroker;

describe('MessageBroker', function () {
    beforeEach(function () {
        $this->broker = new MessageBroker();
    });

    describe('->addMessage()', function () {
        it("should add the given message to the broker's list of messages", function () {
            $tmp = tmpfile();
            $message = new Message($tmp);
            $this->broker->addMessage($message);

            $messages = $this->broker->getMessages();

            expect($messages)->to->contain($message);
        });
    });

    context('when added messages emit events', function () {
        beforeEach(function () {
            $tmp = tmpfile();
            $this->message = new Message($tmp);
            $this->broker->addMessage($this->message);
        });

        it('should emit the same event', function () {
            $val = null;
            $this->broker->on('some.event', function ($x) use (&$val) {
                $val = $x;
            });
            $this->message->emit('some.event', [1]);
            expect($val)->to->equal(1);
        });
    });

    describe('->getStreams()', function () {
        beforeEach(function () {
            $this->stream1 = tmpfile();
            $this->stream2 = tmpfile();
            $this->broker->addMessage(new Message($this->stream1));
            $this->broker->addMessage(new Message($this->stream2));
        });

        it('should return a collection of underlying message streams', function () {
            $streams = $this->broker->getStreams();
            expect($streams[0])->to->equal($this->stream1);
            expect($streams[1])->to->equal($this->stream2);
        });
    });
});
