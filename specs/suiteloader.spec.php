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

        it('should fire an event before loading that passes a total count', function () {
            $count = 0;
            $this->emitter->on('peridot.concurrency.loadstart', function ($total) use (&$count) {
                $count = $total;
            });
            $this->loader->load($this->fixtures);
            expect($count)->to->equal(3);
        });

        it('should fire an event for each file', function() {
            $paths = [];
            $this->emitter->on('peridot.concurrency.suiteloading', function ($path) use (&$paths) {
                $paths[] = $path;
            });
            $this->loader->load($this->fixtures);
            expect($paths)->to->have->length(3)->and->to->satisfy(function($paths) { 
                return sizeof(array_filter($paths, 'file_exists')) === 3;
            });
        });
    });
});
