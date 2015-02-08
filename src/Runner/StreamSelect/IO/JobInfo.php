<?php
namespace Peridot\Concurrency\Runner\StreamSelect\IO;

class JobInfo 
{
    /**
     * @var string
     */
    public $file;

    /**
     * @var \DateTime
     */
    public $start;

    /**
     * @var \DateTime
     */
    public $end;

    /**
     * @param $file
     * @param \DateTime $start
     */
    public function __construct($file, \DateTime $start = null)
    {
        if (is_null($start)) {
            $start = new \DateTime();
        }
        $this->start = $start;
        $this->file = $file;
    }
} 
