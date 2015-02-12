<?php
use Evenement\EventEmitter;
use Peridot\Concurrency\Environment\Environment;
use Peridot\Configuration;

describe('Environment', function () {
    beforeEach(function () {
        $this->emitter = new EventEmitter();
        $this->readStream = tmpfile();
        $this->writeStream = tmpfile();
        $this->environment = new Environment($this->emitter, $this->readStream, $this->writeStream);
    });

    afterEach(function () {
        fclose($this->readStream);
        fclose($this->writeStream);
    });

    describe('->getEventEmitter()', function () {
        it('should return the event emitter', function () {
            expect($this->environment->getEventEmitter())->to->equal($this->emitter);
        });
    });

    describe('->getReadStream()', function () {
        it('should return the read stream', function () {
            expect($this->environment->getReadStream())->to->equal($this->readStream);
        });
    });

    describe('->getWriteStream()', function () {
        it('should return the write stream', function () {
            expect($this->environment->getWriteStream())->to->equal($this->writeStream);
        });
    });

    describe('->load()', function () {
        beforeEach(function () {
            $this->configuration = new Configuration();
            $this->configuration->setDsl(__DIR__ . '/../../fixtures/environment/dsl.php');
            $this->configuration->setConfigurationFile(__DIR__ . '/../../fixtures/environment/peridot.php');
            $this->reader = $this->getProphet()->prophesize('Peridot\Concurrency\Environment\ReaderInterface');
            $this->reader->getConfiguration()->willReturn($this->configuration);
        });

        it('should include the configuration dsl', function () {
            $this->environment->load($this->reader->reveal());
            $radDescribe = superRadDescribe('rad');
            expect($radDescribe)->to->equal('rad');
        });

        it('should execute the callback included in the configuration file', function () {
            $loaded = false;
            $this->emitter->on('environment.load', function () use (&$loaded) {
                $loaded = true;
            });
            $this->environment->load($this->reader->reveal());
            expect($loaded)->to->be->true;
        });

        it('should populate configuration object', function () {
            $this->environment->load($this->reader->reveal());
            expect($this->environment->getConfiguration())->to->equal($this->configuration);
        });
    });
});
