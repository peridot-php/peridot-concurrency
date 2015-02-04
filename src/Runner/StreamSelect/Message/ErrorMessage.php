<?php
namespace Peridot\Concurrency\Runner\StreamSelect\Message;

/**
 * An ErrorMessage is a simple message meant to
 * be backed by stderr. For every data event emitted by this message,
 * an error event will also be emitted.
 *
 * @package Peridot\Concurrency\Runner\StreamSelect\Message
 */
class ErrorMessage extends Message
{
    /**
     * @param resource $resource
     * @param int $chunkSize
     */
    public function __construct($resource, $chunkSize = 4096)
    {
        parent::__construct($resource, $chunkSize);
        $this->on('data', [$this, 'onData']);
    }

    /**
     * Any data received on an error message will emit an error event.
     *
     * @param $data
     * @return void
     */
    public function onData($data)
    {
        $this->emit('error', [$data]);
    }
}
