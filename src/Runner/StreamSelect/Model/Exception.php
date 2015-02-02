<?php
namespace Peridot\Concurrency\Runner\StreamSelect\Model;

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
     * @param int $code
     * @param \Exception $previous
     */
    public function __construct($message = "")
    {
        $this->message = $message;
    }

    /**
     * @param $trace
     * @return $this
     */
    public function setTraceAsString($trace)
    {
        $this->traceAsString = $trace;
        return $this;
    }

    /**
     * @return string
     */
    public function getTraceAsString()
    {
        return $this->traceAsString;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}
