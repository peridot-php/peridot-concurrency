<?php
namespace Peridot\Concurrency\Runner\StreamSelect;

/**
 * Defines an invokable object for opening a resource that
 * sets readable and writeable streams.
 *
 * @package Peridot\Concurrency\Runner\StreamSelect
 */
interface ResourceOpenInterface
{
    /**
     * Open a resource.
     *
     * @param string $executable
     * @param array $descriptor
     * @param array $pipes
     * @return resource
     */
    public function __invoke($executable, array $descriptor, array &$pipes);
}
