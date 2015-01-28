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
     * The Data to be serialized. Indexes represent specific pieces of the
     * message.
     *
     * 0 - a single character showing type: 's' for suite, 't' for test
     * 1 - an event name - i.e suite.start, test.pending, etc.
     * 2 - the test description
     * 3 - the test title
     * 4 - the test status
     * 5 - an exception message if available
     * 6 - an exception trace as string if available
     * 7 - the exception class name
     *
     * @var array
     */
    protected $data;

    /**
     * @param resource $resource
     * @param int $chunkSize
     */
    public function __construct($resource, $chunkSize = 4096)
    {
        parent::__construct($resource, $chunkSize);
        $this->data = [null, null, null, null, null, null, null, null];
    }

    /**
     * Include test information in the message.
     *
     * @param AbstractTest $test
     * @return $this
     */
    public function setTest(AbstractTest $test)
    {
        $this->data[0] = $this->getTypeChar($test);
        $this->data[2] = $test->getDescription();
        $this->data[3] = $test->getTitle();
        return $this;
    }

    /**
     * Include test exception information in the message.
     *
     * @param Exception $exception
     * @return $this
     */
    public function setException(Exception $exception)
    {
        $this->data[5] = $exception->getMessage();
        $this->data[6] = $exception->getTraceAsString();
        $this->data[7] = get_class($exception);
        return $this;
    }

    /**
     * Include an event name in the message.
     *
     * @param string $eventName
     * @return $this
     */
    public function setEvent($eventName)
    {
        $this->data[1] = $eventName;
        return $this;
    }

    /**
     * Include the status in the test message.
     *
     * @param int $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->data[4] = $status;
        return $this;
    }

    /**
     * Write the test message. If content is supplied it will
     * be used instead of the internal serialized data structure.
     *
     * @param string $content
     */
    public function write($content = '')
    {
        if (! $content) {
            $content = serialize($this->data);
        }
        parent::write($content);
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
