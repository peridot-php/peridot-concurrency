<?php
namespace Peridot\Concurrency\Runner\StreamSelect\Message;

use Exception;
use Peridot\Concurrency\Runner\StreamSelect\Model\Exception as ConcurrencyException;
use Peridot\Concurrency\Runner\StreamSelect\Model\Suite;
use Peridot\Concurrency\Runner\StreamSelect\Model\Test;
use Peridot\Core\Test as CoreTest;
use Peridot\Core\AbstractTest;

/**
 * TestMessage writes and reads messages containing Peridot test events.
 *
 * @package Peridot\Concurrency\Runner\StreamSelect\Message
 */
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
     * 5 - the test path
     * 6 - an exception message if available
     * 7 - an exception trace as string if available
     * 8 - the exception class name
     *
     * @var array
     */
    protected $data;

    /**
     * A buffer for storing incoming test message data.
     *
     * @var string
     */
    private $buffer = '';

    /**
     * @param resource $resource
     * @param int $chunkSize
     */
    public function __construct($resource, $chunkSize = 4096)
    {
        parent::__construct($resource, $chunkSize);
        $this->data = [null, null, null, null, null, null, null, null, null];
        $this->on('data', [$this, 'onData']);
        $this->on('end', [$this, 'onEnd']);
    }

    /**
     * Include test information in the message.
     *
     * @param AbstractTest $test
     * @return $this
     */
    public function setTest(AbstractTest $test)
    {
        $packer = $this->getStringPacker();
        $this->data[0] = $this->getTypeChar($test);
        $this->data[2] = $packer->packString($test->getDescription());
        $this->data[3] = $packer->packString($test->getTitle());
        $this->data[5] = $test->getFile();
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
        $packer = $this->getStringPacker();
        $this->data[6] = $packer->packString($exception->getMessage());
        $this->data[7] = $packer->packString($exception->getTraceAsString());
        $this->data[8] = get_class($exception);
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
        $packer = $this->getStringPacker();
        $this->data[1] = $packer->packString($eventName);
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
     * @return void
     */
    public function write($content = '')
    {
        if (! $content) {
            $content = json_encode($this->data);
        }
        parent::write($content);
    }

    /**
     * Handle data received by this message. When complete test messages come in they
     * will be parsed and emitted. When a complete message is received it relays the event
     * name that was received and sends a last argument that is the unpacked message.
     *
     * @param $data
     * @return void
     */
    public function onData($data)
    {
        $this->buffer .= ltrim($data);
        $delimiterPosition = strpos($this->buffer, "\n");

        while ($delimiterPosition !== false) {
            $testMessage = substr($this->buffer, 0, $delimiterPosition);

            if ($testMessage == $this->getTerminateString()) {
                break;
            }

            $this->emitTest($testMessage);

            $this->buffer = substr($this->buffer, $delimiterPosition + 1);
            $delimiterPosition = strpos($this->buffer, "\n");
        }
    }

    /**
     * Reset the buffer.
     *
     * @return void
     */
    public function onEnd()
    {
        $this->clearBuffer();
    }

    /**
     * Return the current buffer of the test message.
     *
     * @return string
     */
    public function getBuffer()
    {
        return $this->buffer;
    }

    /**
     * Clear the message buffer;
     *
     * @return void
     */
    public function clearBuffer()
    {
        $this->buffer = '';
    }

    /**
     * Unpack the testMessage into an array.
     *
     * @param string $testMessage
     * @throws \RuntimeException
     * @return array|null
     */
    private function unpackMessage($testMessage)
    {
        $unpacked = json_decode($testMessage);

        if (!$unpacked) {
            $this->emit('error', ["Illegal message format: $testMessage", $this]);
            $this->clearBuffer();
            return [];
        }

        return $unpacked;
    }

    /**
     * Hydrate a test from an unpacked test message.
     *
     * @param array $unpacked
     * @return \Peridot\Core\TestInterface
     */
    private function hydrateTest(array $unpacked)
    {
        $packer = $this->getStringPacker();
        $description = $packer->unpackString($unpacked[2]);
        $title = $packer->unpackString($unpacked[3]);
        $test = $unpacked[0] == 't' ? new Test($description) : new Suite($description);
        $test->setTitle($title);
        $test->setFile($unpacked[5]);
        return $test;
    }

    /**
     * Emit an appropriate test event. If an exception is included in message
     * data it will be marshaled into an Exception model.
     *
     * @param string $testMessage
     * @return void
     */
    private function emitTest($testMessage)
    {
        $unpacked = $this->unpackMessage($testMessage);

        if (empty($unpacked)) {
            return;
        }

        $test = $this->hydrateTest($unpacked);
        $packer = $this->getStringPacker();

        $args = [$test];
        $event = $packer->unpackString($unpacked[1]);
        if ($event == 'test.failed') {
            $exception = new ConcurrencyException($packer->unpackString($unpacked[6]));
            $exception
                ->setTraceAsString($packer->unpackString($unpacked[7]))
                ->setType($unpacked[8]);
            $args[] = $exception;
        }
        $args[] = $unpacked;
        $this->emit($event, $args);
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
        if ($test instanceof CoreTest) {
            return 't';
        }

        return 's';
    }
}
