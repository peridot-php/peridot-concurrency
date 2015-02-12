<?php
namespace Peridot\Concurrency\Runner\StreamSelect\Application;

use Peridot\Concurrency\Environment\Environment;
use Peridot\Concurrency\Runner\StreamSelect\Message\Message;
use Peridot\Core\TestResult;
use Peridot\Runner\Context;
use Peridot\Runner\Runner;

class RunnerLooper implements LooperInterface
{
    /**
     * Read a suite path on the environment's read stream and execute
     * them against a standard Peridot runner.
     *
     * @param Context $context
     * @param Environment $environment
     * @param Message $message
     */
    public function loop(Context $context, Environment $environment, Message $message)
    {
        while (true) {
            $input = fgets($environment->getReadStream());
            $path = trim($input);
            $context->setFile($path);
            require $path;

            $runner = new Runner(
                $context->getCurrentSuite(),
                $environment->getConfiguration(),
                $environment->getEventEmitter()
            );

            $runner->run(new TestResult($environment->getEventEmitter()));

            $message->end();
            $context->clear();
        }
    }
}
