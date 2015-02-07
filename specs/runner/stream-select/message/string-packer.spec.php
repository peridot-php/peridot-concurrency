<?php
use Peridot\Concurrency\Runner\StreamSelect\Message\StringPacker;

describe('StringPacker', function () {
    beforeEach(function () {
        $this->packer = new StringPacker();
        $this->delimiter = $this->packer->getDelimiter();
        $this->replacement = $this->packer->getReplacement();
    });

    describe('->packString()', function () {
        it('should replace the delimiter with the replacement character', function () {
            $string = "hello{$this->delimiter}world";
            $packed = $this->packer->packString($string);
            expect($packed)->to->equal("hello{$this->replacement}world");
        });
    });

    describe('->unpackString()', function () {
        it('should replace the replacement character with the delimiter', function () {
            $string = "hello{$this->replacement}world";
            $packed = $this->packer->unpackString($string);
            expect($packed)->to->equal("hello{$this->delimiter}world");
        });
    });
});
