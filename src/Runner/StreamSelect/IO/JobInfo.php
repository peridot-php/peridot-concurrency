<?php
namespace Peridot\Concurrency\Runner\StreamSelect\IO;

/**
 * JobInfo contains information about the last job run by a worker.
 *
 * @package Peridot\Concurrency\Runner\StreamSelect\IO
 */
class JobInfo
{
    /**
     * The file associated with the job.
     *
     * @var string
     */
    public $file;

    /**
     * The start time of the job.
     *
     * @var float
     */
    public $start;

    /**
     * The end time of the job.
     *
     * @var float
     */
    public $end;

    /**
     * @param $file
     */
    public function __construct($file)
    {
        $this->file = $file;
        $this->start = microtime(true);
    }

    /**
     * The time elapsed on the job as a formatted time
     * string.
     *
     * @return string
     */
    public function getTimeElapsed()
    {
        return \PHP_Timer::secondsToTimeString($this->end - $this->start);
    }
}
