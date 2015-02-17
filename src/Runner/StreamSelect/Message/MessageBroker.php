<?php
namespace Peridot\Concurrency\Runner\StreamSelect\Message;

use Evenement\EventEmitterTrait;

/**
 * The MessageBroker coordinates the events of multiple messages. Message events
 * bubble up to the broker.
 *
 * @package Peridot\Concurrency\Runner\StreamSelect\Message
 */
class MessageBroker
{
    use EventEmitterTrait;

    /**
     * @var \SplObjectStorage
     */
    protected $messages;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->messages = new \SplObjectStorage();
    }

    /**
     * Add a message to the broker.
     *
     * @param Message $message
     * @return void
     */
    public function addMessage(Message $message)
    {
        $message->setMessageBroker($this);
        $this->messages->attach($message);
    }

    /**
     * Get the messages managed by the broker.
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Remove a message.
     *
     * @param Message $message
     */
    public function removeMessage(Message $message)
    {
        $this->messages->detach($message);
    }

    /**
     * Get all streams belonging to underlying messages.
     *
     * @return array
     */
    public function getStreams()
    {
        $streams = [];
        foreach ($this->messages as $message) {
            $streams[] = $message->getResource();
        }
        return $streams;
    }

    /**
     * Attempt to read from messages.
     *
     * @return void
     */
    public function read()
    {
        $read = $this->getStreams();
        $write = null;
        $except = null;
        $modified = stream_select($read, $write, $except, 0, 200000);

        if ($modified === false) {
            throw new \RuntimeException("stream_select() returned an error");
        }

        foreach ($read as $resource) {
            foreach ($this->messages as $message) {
                $this->readResource($message, $resource);
            }
        }
    }

    /**
     * If the given message owns the passed in resource, then
     * read data on that message.
     *
     * @param Message $message
     * @param resource $resource
     * @return void
     */
    protected function readResource(Message $message, $resource)
    {
        if ($message->getResource() === $resource) {
            $message->receive();
        }
    }
}
