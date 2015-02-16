<?php
use Evenement\EventEmitterInterface;
use Peridot\Concurrency\ConcurrencyPlugin;
use Peridot\Console\Environment;
use Peridot\Plugin\Prophecy\ProphecyPlugin;
use Peridot\Reporter\Dot\DotReporterPlugin;

return function (EventEmitterInterface $emitter) {
    $prophecy = new ProphecyPlugin($emitter);
    $concurrency = new ConcurrencyPlugin($emitter);
    $dot = new DotReporterPlugin($emitter);

    $emitter->on('peridot.start', function (Environment $env) {
        $definition = $env->getDefinition();
        $definition->getArgument('path')->setDefault('specs');
    });

    $debug = getenv('DEBUG');
    if ($debug) {
        $emitter->on('error', function ($number, $message, $file, $line) {
            print "Error: $number - $message:$file:$line\n";
        });
    }

    /***********************************************
     * Code Coverage - @todo extract lib for this
     ***********************************************/
    $coverageType = getenv('CODE_COVERAGE');
    $coverage = new \PHP_CodeCoverage();

    /**
     * Execute a function if a concurrency token is
     * available, and coverage is enabled. The token
     * and coverage are passed to the callback.
     *
     * @param callable $func
     */
    $covered = function (callable $func) use ($coverageType, $coverage) {
        $token = getenv('PERIDOT_TEST_TOKEN'); //this is only present in a concurrent context
        if ($coverageType && $token) {
            $func($token, $coverage);
        }
    };

    /**
     * Return all Peridot code coverage files.
     *
     * @return array
     */
    $coverageFiles = function () {
        $path = sys_get_temp_dir();
        $coverageFiles = glob($path . '/PCC_*');
        return $coverageFiles;
    };

    /**
     * When a runner starts out of process, create a new coverage
     * object.
     */
    $emitter->on('runner.start', function () use ($covered) {
        $covered(function ($token, \PHP_CodeCoverage $coverage) {
            $coverage->filter()->addDirectoryToWhitelist(__DIR__ . '/src');
            $coverage->start($token);
        });
    });

    /**
     * When a runner ends out of process, write it to a temp file.
     */
    $emitter->on('runner.end', function () use ($covered) {
        $covered(function ($token, \PHP_CodeCoverage $coverage) {
            $coverage->stop();
            $writer = new \PHP_CodeCoverage_Report_PHP();
            $file = tempnam(sys_get_temp_dir(), "PCC_{$token}_");
            $writer->process($coverage, $file);
        });
    });

    /**
     * When peridot ends in the main process, we aggregate all of our code coverage
     * results.
     */
    $emitter->on('peridot.end', function () use ($coverageType, $coverageFiles) {
        if (! $coverageType) {
            return;
        }

        $coverages = [];
        $files = $coverageFiles();
        foreach ($files as $file) {
            $fileCoverage = file_get_contents($file);

            if (substr($fileCoverage, 0, 5) === '<?php') {
                $coverageObject = include $file;
                $coverages[] = $coverageObject;
            }

            unlink($file);
        }

        /**
         * Merge all of the results into a single coverage object.
         */
        $coverage = array_reduce($coverages, function ($result, $obj) {
            if (is_null($result)) {
                return $obj;
            }
            $result->merge($obj);
            return $result;
        });

        //output total coverage
        $path = __DIR__ . '/tmp/report';
        $writer = new \PHP_CodeCoverage_Report_HTML();

        if ($coverageType == 'clover') {
            $path = __DIR__ . '/build/logs/clover.xml';
            $writer = new \PHP_CodeCoverage_Report_Clover();
        }

        $writer->process($coverage, $path);
    });
};
