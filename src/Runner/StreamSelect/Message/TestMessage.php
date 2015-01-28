<?php
namespace Peridot\Concurrency\Runner\StreamSelect\Message;

use Exception;
use Peridot\Core\AbstractTest;
use Peridot\Core\Test;

class TestMessage extends Message
{
    /**
     * @var int
     */
    const TEST_PENDING = 2;

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
     * @param int $status - status of the test. 0 for fail, 1 for pass, 2 for pending. -1 means no status
     * @param Exception|null $exception
     */
    public function writeTest(AbstractTest $test, $status = -1, Exception $exception = null)
    {
        $data = [
            $this->getTypeChar($test),
            $test->getDescription(),
            $status,
            $test->getTitle(),
        ];

        if (!is_null($exception)) {
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
