<?php
namespace Peridot\Concurrency\Runner\StreamSelect;

use Peridot\Core\HasEventEmitterTrait;
use Evenement\EventEmitterInterface;

class Worker
{
    use HasEventEmitterTrait;

    /**
     * @var string
     */
    protected $executable;

    /**
     * @param string $executable a string to execute via proc_open
     * @param EventEmitterInterface $eventEmitter
     */
    public function __construct($executable, EventEmitterInterface $eventEmitter)
    {
        $this->executable = $executable;
        $this->eventEmitter = $emitter;
    }
}
