<?php
namespace Peridot\Concurrency\Runner\StreamSelect\Message;

use Evenement\EventEmitterTrait;

/**
 * A ReadableMessage is meant to receive
 * data from a stream.
 *
 * @package Peridot\Concurrency\Runner\StreamSelect\Message
 */
class ReadableMessage
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
        fseek($this->resource, $this->offset);
        while ($content = fread($this->resource, $this->chunkSize)) {
            $this->content .= $content;
            $this->emit('data', [$content]);
        }
        $this->offset = ftell($this->resource);
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
} 
