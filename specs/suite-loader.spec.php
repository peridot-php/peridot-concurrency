<?php
use Peridot\Concurrency\SuiteLoader;
use Evenement\EventEmitter;

describe('SuiteLoader', function () {
    $this->fixtures = __DIR__ . '/../fixtures/suiteloader';

    describe('->load()', function () {
        beforeEach(function () {
            $this->emitter = new EventEmitter();
            $this->loader = new SuiteLoader('*.spec.php', $this->emitter);
        });

        it('should fire an event for matched suites', function () {
            $tests = 0;
            $this->emitter->on('peridot.concurrency.load', function ($loaded) use (&$tests) {
                $tests = $loaded;
            });
            $this->loader->load($this->fixtures);
            expect($tests)->to->have->length(3);
        });
    });
});
