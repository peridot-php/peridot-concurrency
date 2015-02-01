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
} 
