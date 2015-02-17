<?php
namespace Peridot\Concurrency\Runner\StreamSelect\Application;

use Peridot\Concurrency\Environment\Environment;
use Peridot\Concurrency\Runner\StreamSelect\Message\Message;
use Peridot\Runner\Context;

/**
 * The LooperInterface serves as an interface to an application loop.
 *
 * @package Peridot\Concurrency\Runner\StreamSelect\Application
 */
interface LooperInterface
{
    /**
     * @param Context $context
     * @param Environment $environment
     * @param Message $message
     * @return mixed
     */
    public function loop(Context $context, Environment $environment, Message $message);
}
