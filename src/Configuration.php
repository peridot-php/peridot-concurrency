<?php
namespace Peridot\Concurrency;

use Peridot\Configuration as CoreConfiguration;

/**
 * Configuration wraps the core Peridot Configuration object. It provides accessors
 * to the configured process count, and delegates all other configuration requests to
 * the wrapped configuration object.
 *
 * @package Peridot\Concurrency
 */
class Configuration
{
    /**
     * @var \Peridot\Configuration
     */
    protected $config;

    /**
     * @var int
     */
    protected $processes = 5;

    /**
     * @param \Peridot\Configuration $config
     */
    public function __construct(CoreConfiguration $config)
    {
        $this->config = $config;
    }

    /**
     * Get the number of processes to use in process based
     * concurrency.
     *
     * @return int
     */
    public function getProcesses()
    {
        return $this->processes;
    }

    /**
     * Set the number of processes to use in process based
     * concurrency.
     *
     * @param int $numProcs
     * @return $this
     */
    public function setProcesses($numProcs)
    {
        $this->processes = $numProcs;
        return $this;
    }

    /**
     * Delegate method calls to underlying core config.
     *
     * @param string $methodName
     * @param array $args
     * @return mixed
     */
    public function __call($methodName, $args)
    {
        if (! method_exists($this->config, $methodName)) {
            throw new \BadMethodCallException("Config method $methodName does not exist");
        }
        return call_user_func_array([$this->config, $methodName], $args);
    }
}
