<?php
namespace Peridot\Concurrency\Runner\StreamSelect\Application;

use Peridot\Concurrency\Environment\Environment;
use Peridot\Concurrency\Runner\StreamSelect\Message\Message;
use Peridot\Runner\Context;

interface LooperInterface
{
    public function loop(Context $context, Environment $environment, Message $message);
} 
