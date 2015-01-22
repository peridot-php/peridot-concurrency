<?php
use Peridot\Configuration;
use Peridot\Concurrency\Environment\Reader;

describe('Reader', function () {

    beforeEach(function () {
        $configuration = new Configuration();

        //write config to environment
        $configuration->setDsl(__FILE__);
        $configuration->setGrep('*.test.php');
        $configuration->setPath('/path/to/tests');
        $configuration->setReporter('reporter');
        $configuration->disableColors();
        $configuration->stopOnFailure();

        $this->configuration = $configuration;
        $this->reader = new Reader(new Configuration());
    });

    describe('->getConfiguration()', function () {
        it('should fetch a configuration object populated by environment', function () {
            $config = $this->reader->getConfiguration();

            expect($config->getDsl())->to->equal($this->configuration->getDsl());
            expect($config->getGrep())->to->equal($this->configuration->getGrep());
            expect($config->getPath())->to->equal($this->configuration->getPath());
            expect($config->getReporter())->to->equal($this->configuration->getReporter());
            expect($config->areColorsEnabled())->to->equal($this->configuration->areColorsEnabled());
            expect($config->shouldStopOnFailure())->to->equal($this->configuration->shouldStopOnFailure());
        });
    });
});
