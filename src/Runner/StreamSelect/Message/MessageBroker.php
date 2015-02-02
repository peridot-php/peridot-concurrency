<?php
namespace Peridot\Concurrency\Runner\StreamSelect\Message;

use Evenement\EventEmitterTrait;

class MessageBroker
{
    use EventEmitterTrait;

    /**
     * @var array
     */
    protected $messages;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->messages = [];
    }

    /**
     * Add a message to the broker.
     *
     * @param Message $message
     */
    public function addMessage(Message $message)
    {
        $message->setMessageBroker($this);
        $this->messages[] = $message;
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Get all streams belonging to underlying messages.
     *
     * @return array
     */
    public function getStreams()
    {
        return array_map(function (Message $message) {
            return $message->getResource();
        }, $this->messages);
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
     */
    protected function readResource(Message $message, $resource)
    {
        if ($message->getResource() === $resource) {
            $message->receive();
        }
    }
}
