<?php
namespace Peridot\Concurrency\Runner\StreamSelect;

/**
 * Opens a process via the native proc_open function.
 *
 * @package Peridot\Concurrency\Runner\StreamSelect
 */
class ProcOpen implements ProcessOpenerInterface
{
    /**
     * {@inheritdoc}
     *
     * @return resource
     */
    public function __invoke($executable, array $descriptor, array &$pipes)
    {
        return proc_open($executable, $descriptor, $pipes);
    }
}
