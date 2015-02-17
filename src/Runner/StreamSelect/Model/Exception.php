<?php
namespace Peridot\Concurrency\Runner\StreamSelect\Model;

/**
 * Exception models an actual PHP Exception.
 *
 * @package Peridot\Concurrency\Runner\StreamSelect\Model
 */
class Exception
{
    private $message;

    /**
     * @var string
     */
    private $traceAsString;

    /**
     * @var string
     */
    private $type;

    /**
     * @param string $message
     */
    public function __construct($message = "")
    {
        $this->message = $message;
    }

    /**
     * Set the exception trace as a string.
     *
     * @param $trace
     * @return $this
     */
    public function setTraceAsString($trace)
    {
        $this->traceAsString = $trace;
        return $this;
    }

    /**
     * Get the exception trace as a tring.
     *
     * @return string
     */
    public function getTraceAsString()
    {
        return $this->traceAsString;
    }

    /**
     * Get the exception message.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set the exception type.
     *
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Return the exception type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}
