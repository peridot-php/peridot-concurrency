<?php
namespace Peridot\Concurrency\Runner\StreamSelect;

/**
 * Creates resources from tmpfiles.
 *
 * @package Peridot\Concurrency\Runner\StreamSelect
 */
class TmpfileOpen implements ResourceOpenInterface
{
    protected $tempfiles = [];

    /**
     * Creates a tempfile and fills the pipes array with
     * three additional pipes to simulate read, write, and error streams.
     *
     * @return resource
     */
    public function __invoke($executable, array $descriptor, array &$pipes)
    {
        $main = tmpfile();

        $readable = tmpfile();
        $writable = tmpfile();
        $error = tmpfile();

        $pipes = [$readable, $writable, $error];

        $this->tempfiles[] = $main;
        $this->tempfiles[] = $readable;
        $this->tempfiles[] = $writable;
        $this->tempfiles[] = $error;

        return $main;
    }

    /**
     * Clean up temp resources
     */
    public function __destruct()
    {
        foreach ($this->tempfiles as $temp) {
            fclose($temp);
        }
    }
}
