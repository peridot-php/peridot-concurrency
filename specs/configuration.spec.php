<?php
use Peridot\Concurrency\Configuration;
use Peridot\Configuration as CoreConfiguration;

describe('Configuration', function () {
    context('when attempting to access an unknown method', function () {
        it('should delegate to core configuration', function () {
            $core = new CoreConfiguration();
            $core->setGrep('*.test.php');
            $config = new Configuration($core);
            expect($config->getGrep())->to->equal('*.test.php');
        });

        it('should throw an exception if method does not exist', function () {
            $core = new CoreConfiguration();
            $config = new Configuration($core);
            expect([$config, 'getMysteryValue'])->to->throw('BadMethodCallException');
        });
    });

    describe('process accessors', function () {
        it('should allow access to process configuration property', function () {
            $config = new Configuration(new CoreConfiguration());
            expect($config->getProcesses())->to->equal(5);
            $config->setProcesses(4);
            expect($config->getProcesses())->to->equal(4);
        });
    });
});
