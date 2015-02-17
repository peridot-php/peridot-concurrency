<?php
namespace Peridot\Concurrency\Environment;

use Peridot\Configuration;

/**
 * Reader reads Peridot environment information into a usable
 * Configuration object.
 *
 * @package Peridot\Concurrency\Environment
 */
class Reader implements ReaderInterface
{
    /**
     * @var Configuration
     */
    protected $config;

    /**
     * @var array
     */
    protected $mappings = [];

    /**
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
        $this->initMappings($config);
    }

    /**
     * {@inheritdoc}
     *
     * @return Configuration
     */
    public function getConfiguration()
    {
        foreach ($this->mappings as $env => $callable) {
            $value = getenv($env);
            if ($value !== false) {
                $func = array_slice($callable, 0, 2);
                $args = array_slice($callable, 2);
                array_unshift($args, $value);
                call_user_func_array($func, $args);
            }
        }
        return $this->config;
    }

    /**
     * Return a map of environment variables to callables.
     *
     * @param Configuration $config
     * @return array
     */
    private function initMappings(Configuration $config)
    {
        $this->mappings = [
            'PERIDOT_GREP' => [$config, 'setGrep'],
            'PERIDOT_REPORTER' => [$config, 'setReporter'],
            'PERIDOT_PATH' => [$config, 'setPath'],
            'PERIDOT_COLORS_ENABLED' => [$this, 'handleBooleanEnv', false, [$config, 'disableColors']],
            'PERIDOT_STOP_ON_FAILURE' => [$this, 'handleBooleanEnv', true, [$config, 'stopOnFailure']],
            'PERIDOT_DSL' => [$config, 'setDsl'],
            'PERIDOT_CONFIGURATION_FILE' => [$config, 'setConfigurationFile']
        ];
    }

    /**
     * Handle a boolean env variable. Calls the provided callable if the value
     * matches the expected condition.
     *
     * @param string $value
     * @param bool $condition
     * @param callable $callable
     */
    private function handleBooleanEnv($value, $condition, callable $callable)
    {
        if ((bool) $value == $condition) {
            call_user_func($callable);
        }
    }
}
