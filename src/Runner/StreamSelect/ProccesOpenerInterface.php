<?php
namespace Peridot\Concurrency\Runner\StreamSelect;

/**
 * Defines an invokable object for opening a process.
 *
 * @package Peridot\Concurrency\Runner\StreamSelect
 */
interface ProcessOpenerInterface
{
    /**
     * Open a process.
     *
     * @param string $executable
     * @param array $descriptor
     * @param array $pipes
     * @return resource
     */
    public function __invoke($executable, array $descriptor, array &$pipes);
}
