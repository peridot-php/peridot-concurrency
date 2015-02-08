<?php
use Peridot\Concurrency\Runner\StreamSelect\IO\JobInfo;

describe('JobInfo', function () {
    beforeEach(function () {
        $this->info = new JobInfo('/path/to/file.php');
        $this->info->end = microtime(true);
    });

    describe('->getTimeElapsed()', function () {
        it('should return a formatted time string', function () {
            $formatted = PHP_Timer::secondsToTimeString($this->info->end - $this->info->start);
            expect($this->info->getTimeElapsed())->to->equal($formatted);
        });
    });
});
