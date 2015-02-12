<?php
namespace Peridot\Concurrency\Environment;

use Peridot\Configuration;

/**
 * ReaderInterface reads Peridot environment information.
 *
 * @package Peridot\Concurrency\Environment
 */
interface ReaderInterface
{
    /**
     * Returns the configuration object populated by
     * Peridot environment variables.
     *
     * @return Configuration
     */
    public function getConfiguration();
}
