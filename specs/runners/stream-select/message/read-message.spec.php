<?php
describe('Message', function () {
    beforeEach(function () {
        $this->resource = tmpfile();
    });

    describe('->receive()', function () {
        it('should read from a stream', function () {
            $message = new Message($this->resource);
            fwrite($this->resource, "hello world");
            $message->receive();
            expect($message->getContent())->to->equal('hello world');
        });

        it('should read until no more content', function () {
            $message = new Message($this->resource, 1);
            fwrite($this->resource, "hello world");
            $message->receive();
            expect($message->getContent())->to->equal('hello world');
        });
    });

    describe('->getResource()', function() {
        it('should return the underlying resource', function () {
            $message = new Message($this->resource);
            expect($message->getResource())->to->equal($this->resource);
        });
    });
});

class Message
{
    /**
     * @var resource
     */
    protected $resource;

    /**
     * @var int
     */
    protected $chunkSize;

    /**
     * @var string
     */
    protected $content;

    /**
     * @var int
     */
    protected $offset;

    /**
     * @param resource $resource
     * @param int $chunkSize
     */
    public function __construct($resource, $chunkSize = 4096)
    {
        $this->resource = $resource;
        $this->chunkSize = $chunkSize;
        $this->content = '';
        $this->offset = ftell($resource);
    }

    /**
     * Read data from a stream. Currently read content
     * can be fetched with getContent().
     *
     * @return void
     */
    public function receive()
    {
        fseek($this->resource, $this->offset);
        while ($content = fread($this->resource, $this->chunkSize)) {
            $this->content .= $content;
        }
        $this->offset = ftell($this->resource);
    }

    /**
     * Get content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Get the resource.
     *
     * @return resource
     */
    public function getResource()
    {
        return $this->resource;
    }
}
