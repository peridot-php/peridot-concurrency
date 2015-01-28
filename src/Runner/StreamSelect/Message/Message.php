<?php
namespace Peridot\Concurrency\Runner\StreamSelect\Message;

use Evenement\EventEmitterTrait;

/**
 * A ReadableMessage is meant to receive
 * data from a stream.
 *
 * @package Peridot\Concurrency\Runner\StreamSelect\Message
 */
class Message
{
    use EventEmitterTrait;

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
} 
