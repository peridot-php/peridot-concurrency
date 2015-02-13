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
     * @var string
     */
    protected $id;

    /**
     * @var JobInfo
     */
    protected $jobInfo;

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
        $this->id = uniqid();
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
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
            [$this->executable, self::$descriptorspec, &$pipes]
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
     * @return bool
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
        $this->jobInfo = new JobInfo($testPath);
        $data = $testPath . "\n";
        fwrite($this->getInputStream(), $data);
        $this->running = true;
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
    public function getProcess()
    {
        return $this->process;
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

    /**
     * {@inheritdoc}
     *
     * @param $stream
     * @return bool
     */
    public function hasStream($stream)
    {
        $isInputStream = $stream == $this->getInputStream();
        $isOutputStream = $stream == $this->getOutputStream();
        $isErrStream = $stream == $this->getErrorStream();

        return $isInputStream || $isOutputStream || $isErrStream;
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function free()
    {
        $this->running = false;
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function close()
    {
        $this->free();
        $this->started = false;
        foreach ($this->pipes as $pipe) {
            fclose($pipe);
        }

        $this->closeProcess();
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * {@inheritdoc}
     *
     * @return JobInfo
     */
    public function getJobInfo()
    {
        return $this->jobInfo;
    }

    /**
     * Close the process opened by this worker.
     *
     * @return void
     */
    protected function closeProcess()
    {
        if (! is_resource($this->process)) {
            return;
        }

        $procCloseFn = 'proc_close';
        if (!$this->resourceOpen instanceof ProcOpen) {
            $procCloseFn = 'fclose';
        }

        call_user_func($procCloseFn, $this->process);
    }
}
