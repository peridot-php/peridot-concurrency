<?php
namespace Peridot\Concurrency\Runner\StreamSelect\Message;

use Peridot\Core\AbstractTest;
use Peridot\Core\Test;

class TestMessage extends Message
{
    /**
     * @var int
     */
    const TEST_PASS = 1;

    /**
     * @var int
     */
    const TEST_FAIL = 0;

    /**
     * Write test information to a stream.
     *
     * @param AbstractTest $test
     * @param $passOrFail
     */
    public function writeTest(AbstractTest $test, $passOrFail, \Exception $exception = null)
    {
        $data = [
            $this->getTypeChar($test),
            $test->getDescription(),
            $passOrFail,
            $test->getTitle(),
        ];

        if ($exception) {
            $data = array_merge($data, [
                $exception->getMessage(),
                $exception->getTraceAsString(),
                get_class($exception)
            ]);
        }

        parent::write(serialize($data));
    }

    /**
     * Get a single char used for identifying the type of AbstractTest
     * being used in the message.
     *
     * @param AbstractTest $test
     * @return string
     */
    private function getTypeChar(AbstractTest $test)
    {
        if ($test instanceof Test) {
            return 't';
        }

        return 's';
    }
} 
