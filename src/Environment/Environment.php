<?php
namespace Peridot\Concurrency\Environment;

use Evenement\EventEmitterInterface;
use Peridot\Configuration;
use Peridot\Runner\Context;

/**
 * The Environment contains configuration and stream information
 * for concurrency operations.
 *
 * @package Peridot\Concurrency\Environment
 */
class Environment
{
    /**
     * @var EventEmitterInterface
     */
    protected $emitter;

    /**
     * @var resource
     */
    protected $readStream;

    /**
     * @var resource
     */
    protected $writeStream;

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @param EventEmitterInterface $emitter
     */
    public function __construct(EventEmitterInterface $emitter, $readStream, $writeStream)
    {
        $this->emitter = $emitter;
        $this->readStream = $readStream;
        $this->writeStream = $writeStream;
        $this->initializeContext($this->emitter);
    }

    /**
     * Load the Peridot environment.
     *
     * @return void
     */
    public function load(ReaderInterface $reader)
    {
        $this->configuration = $reader->getConfiguration();
        require_once $this->configuration->getDsl();

        $peridotCallback = include $this->configuration->getConfigurationFile();
        if (is_callable($peridotCallback)) {
            call_user_func($peridotCallback, $this->emitter);
        }
    }

    /**
     * Return the environments event emitter.
     *
     * @return EventEmitterInterface
     */
    public function getEventEmitter()
    {
        return $this->emitter;
    }

    /**
     * Return the read stream used by the environment.
     *
     * @return resource
     */
    public function getReadStream()
    {
        return $this->readStream;
    }

    /**
     * Return the write stream used by the environment.
     *
     * @return resource
     */
    public function getWriteStream()
    {
        return $this->writeStream;
    }

    /**
     * Get the configuration used by the Environment.
     *
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * Initialize the Context with the same EventEmitter as the Environment.
     *
     * @return void
     */
    protected function initializeContext(EventEmitterInterface $emitter)
    {
        Context::getInstance()->setEventEmitter($emitter);
    }
}
