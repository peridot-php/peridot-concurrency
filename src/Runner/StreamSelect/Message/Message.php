<?php
namespace Peridot\Concurrency\Runner\StreamSelect\Message;

use Evenement\EventEmitter;

/**
 * A ReadableMessage is meant to receive
 * data from a stream.
 *
 * @package Peridot\Concurrency\Runner\StreamSelect\Message
 */
class Message extends EventEmitter
{
    /**
     * @var resource
     */
    protected $resource;

    /**
     * @var int
     */
    protected $chunkSize;

    /**
     * @var string
     */
    protected $content;

    /**
     * @var int
     */
    protected $offset;

    /**
     * @var bool
     */
    protected $readable = false;

    /**
     * @var bool
     */
    protected $writable = false;

    /**
     * @var StringPacker
     */
    protected $stringPacker;

    /**
     * @var MessageBroker
     */
    protected $broker;

    /**
     * @param resource $resource
     * @param int $chunkSize
     */
    public function __construct($resource, $chunkSize = 4096)
    {
        $this->resource = $resource;
        $this->chunkSize = $chunkSize;
        $this->content = '';
        $this->offset = ftell($resource);
    }

    /**
     * Read data from a stream. Currently read content
     * can be fetched with getContent().
     *
     * @return void
     */
    public function receive()
    {
        if ($this->isWritable()) {
            throw new \RuntimeException('Cannot read from writable message');
        }

        $this->readable = true;
        fseek($this->resource, $this->offset);
        while ($content = fread($this->resource, $this->chunkSize)) {
            $this->content .= $content;
            $this->emit('data', [$content]);
            if (strpos($content, $this->getTerminateString()) !== false) {
                $this->emit('end', [$this]);
            }
        }
        $this->offset = ftell($this->resource);
    }

    /**
     * Write content to the stream.
     *
     * @param string $content
     */
    public function write($content)
    {
        if ($this->isReadable()) {
            throw new \RuntimeException("Cannot write to a readable message");
        }

        $this->writable = true;
        $content = trim($content);
        fwrite($this->resource, $content . "\n");
    }

    /**
     * Write content to the stream and send a signal
     * to terminate. It is up to the given message to
     * determine what the end signal is.
     *
     * @param $content
     */
    public function end($content = '')
    {
        if ($content) {
            $this->write($content);
        }
        $this->write($this->getTerminateString());
    }

    /**
     * Get content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Get the resource.
     *
     * @return resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Return if this message is a readable message.
     *
     * @return bool
     */
    public function isReadable()
    {
        return $this->readable;
    }

    /**
     * @return bool
     */
    public function isWritable()
    {
        return $this->writable;
    }

    /**
     * Return the string used to represent a terminated write.
     *
     * @return string
     */
    public function getTerminateString()
    {
        return "TERMINATE";
    }

    /**
     * Get the packer used by this message.
     *
     * @return StringPacker
     */
    public function getStringPacker()
    {
        if (is_null($this->stringPacker)) {
            $packer = new StringPacker();
            $this->stringPacker = $packer;
        }
        return $this->stringPacker;
    }

    /**
     * Set the packer used by this message.
     *
     * @param StringPacker $packer
     * @return $this
     */
    public function setStringPacker(StringPacker $packer)
    {
        $this->stringPacker = $packer;
        return $this;
    }

    /**
     * Set the MessageBroker this message belongs to.
     *
     * @param MessageBroker $broker
     */
    public function setMessageBroker(MessageBroker $broker)
    {
        $this->broker = $broker;
    }

    /**
     * Get the MessageBroker this message belongs to.
     *
     * @return MessageBroker
     */
    public function getMessageBroker()
    {
        return $this->broker;
    }

    /**
     * Emit broadcasts an event from the message, and if the broker
     * is set on this message, the same event is broadcast on the broker.
     *
     * @param $event
     * @param array $arguments
     */
    public function emit($event, array $arguments = [])
    {
        parent::emit($event, $arguments);
        if ($this->broker) {
            $this->broker->emit($event, $arguments);
        }
    }
} 
