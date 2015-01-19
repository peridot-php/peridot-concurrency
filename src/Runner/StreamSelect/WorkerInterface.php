<?php
namespace Peridot\Concurrency\Runner\StreamSelect;

interface WorkerInterface
{
    /**
     * Start the worker process.
     *
     * @return void
     */
    public function start();

    /**
     * Return the stream that the process receives input on.
     *
     * @return resource
     */
    public function getInputStream();

    /**
     * Return the stream that the process writes to. Returns
     * the stream as a non-blocking resource.
     *
     * @return resource
     */
    public function getOutputStream();

    /**
     * Return the stream that the process writes errors to. Returns
     * the stream as a non-blocking resource.
     *
     * @return resource
     */
    public function getErrorStream();
}
