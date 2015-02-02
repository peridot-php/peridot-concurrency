<?php
namespace Peridot\Concurrency\Runner\StreamSelect\Message;

/**
 * StringPacker packs strings into a format suitable
 * for transmission in messages by replacing message delimiters
 * with a safer character.
 *
 * @package Peridot\Concurrency\Runner\StreamSelect\Message
 */
class StringPacker
{
    /**
     * The delimiter to replace in strings.
     *
     * @var string
     */
    protected $delimiter;

    /**
     * The replacement character.
     *
     * @var string
     */
    protected $replacement;

    /**
     * @param string $delimiter
     * @param string $replacement - defaults to unicode bell character
     */
    public function __construct($delimiter = "\n", $replacement = '\u0007')
    {
        $this->delimiter = $delimiter;
        $this->replacement = $this->encode($replacement);
    }

    /**
     * Replace new lines with a format that does not conflict with
     * parsing a test message.
     *
     * @param $str
     * @return string
     */
    public function packString($str)
    {
        return str_replace("\n", $this->replacement, $str);
    }

    /**
     * Replaces new line replacements with an actual new line.
     *
     * @param $str
     * @return string
     */
    public function unpackString($str)
    {
        return str_replace($this->replacement, "\n", $str);
    }

    /**
     * Return the delimiter being searched for.
     *
     * @return string
     */
    public function getDelimiter()
    {
        return $this->delimiter;
    }

    /**
     * Get the replacement character used by the string packer.
     *
     * @return mixed|string
     */
    public function getReplacement()
    {
        return $this->replacement;
    }

    /**
     * Since the json extension supports unicode, we use it to
     * encode our unicode characters.
     *
     * @param $str
     * @return mixed
     */
    protected function encode($str)
    {
        return json_decode('"' . $str . '"');
    }
}
