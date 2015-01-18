<?php
use Peridot\Concurrency\SuiteLoader;
use Evenement\EventEmitter;

describe('SuiteLoader', function () {
    $this->fixtures = __DIR__ . '/../fixtures/suiteloader';

    describe('->load()', function () {
        it('should fire an event for each file', function() {
            $emitter = new EventEmitter();
            $paths = [];
            $emitter->on('peridot.concurrency.suiteloading', function ($path) use (&$paths) {
                $paths[] = $path;
            });

            $loader = new SuiteLoader('*.spec.php', $emitter);
            $loader->load($this->fixtures);

            expect($paths)->to->have->length(3)->and->to->satisfy(function($paths) { 
                return sizeof(array_filter($paths, 'file_exists')) === 3;
            });
        });
    });
});
