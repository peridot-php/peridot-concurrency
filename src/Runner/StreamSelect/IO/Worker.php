<?php
namespace Peridot\Concurrency\Runner\StreamSelect\IO;

use Peridot\Core\HasEventEmitterTrait;
use Evenement\EventEmitterInterface;

/**
 * A Worker opens and manages a single process.
 *
 * @package Peridot\Concurrency\Runner\StreamSelect
 */
class Worker implements WorkerInterface
{
    use HasEventEmitterTrait;

    /**
     * @var string
     */
    protected $executable;

    /**
     * @var resource
     */
    protected $process;

    /**
     * @var bool
     */
    protected $running = false;

    /**
     * @var bool
     */
    protected $started = false;

    /**
     * @var array
     */
    private $pipes = [];

    /**
     * @var \Peridot\Concurrency\Runner\StreamSelect\IO\ResourceOpenInterface
     */
    private $resourceOpen;

    /**
     * Descriptor for proc_open. Defines
     * 0 => readable STDIN
     * 1 => writeable STDOUT
     * 2 => writeable STDERR
     */
    private static $descriptorspec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ];

    /**
     * @param string $executable a string to execute via proc_open
     * @param EventEmitterInterface $eventEmitter
     */
    public function __construct(
        $executable,
        EventEmitterInterface $eventEmitter,
        ResourceOpenInterface $opener = null
    ) {
        $this->executable = $executable;
        $this->eventEmitter = $eventEmitter;
        $this->resourceOpen = $opener ?: new ProcOpen();
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function start()
    {
        $pipes = [];

        $this->process = call_user_func_array(
            $this->resourceOpen,
            [$this->executable, self::$descriptorspec, $pipes]
        );

        // make output and error streams non-blocking streams
        stream_set_blocking($pipes[1], 0);
        stream_set_blocking($pipes[2], 0);

        $this->pipes = $pipes;
        $this->started = true;
    }

    /**
     * {@inheritdoc}
     *
     * @retun bool
     */
    public function isStarted()
    {
        return $this->started;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $testPath
     * @return void
     */
    public function run($testPath)
    {
        $data = $testPath . "\n";
        fwrite($this->getInputStream(), $data);
        $this->isRunning = true;
        $this->eventEmitter->emit('peridot.concurrency.worker.run', [$this]);
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function isRunning()
    {
        return $this->running;
    }

    /**
     * {@inheritdoc}
     *
     * @return resource
     */
    public function getInputStream()
    {
        return $this->pipes[0];
    }

    /**
     * {@inheritdoc}
     *
     * @return resource
     */
    public function getOutputStream()
    {
        return $this->pipes[1];
    }

    /**
     * {@inheritdoc}
     *
     * @return resource
     */
    public function getErrorStream()
    {
        return $this->pipes[2];
    }
}
