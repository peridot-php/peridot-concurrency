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
            list($token, $path) = $this->getTestInfo($input);
            $context->setFile($path);
            putenv("PERIDOT_TEST_TOKEN=$token");
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

    /**
     * Parse input to get a test token and test path.
     *
     * @param $input
     * @return array
     */
    public function getTestInfo($input)
    {
        $message = trim($input);
        $parts = explode(':', $message);
        $index = count($parts) - 1;
        $token = $parts[$index];
        $path = implode(':', array_slice($parts, 0, $index));
        return [$token, $path];
    }
}
