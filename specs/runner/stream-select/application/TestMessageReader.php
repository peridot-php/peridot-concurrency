<?php
use Peridot\Scope\Scope;

class TestMessageReader extends Scope
{
    /**
     * Read a stream used by a TestMessage and return
     * the serialized value.
     *
     * @param $stream
     * @return mixed
     */
    public function readMessage($stream)
    {
        fseek($stream, 0);
        $contents = stream_get_contents($stream);
        return json_decode($contents);
    }

    /**
     * Match message values against a set of values.
     *
     * @param $stream
     * @param array $values
     */
    public function expectMessageValues($stream, array $values)
    {
        $decoded = $this->readMessage($stream);
        foreach ($values as $value) {
            expect($decoded)->to->include($value);
        }
    }
} 
