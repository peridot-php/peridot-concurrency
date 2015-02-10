<?php
use Peridot\Concurrency\Runner\StreamSelect\IO\ProcOpen;

describe('ProcOpen', function () {
    it('should open a process and its pipes', function () {
        $open = new ProcOpen();
        $descriptor = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        $pipes = [];
        $process = call_user_func_array($open, ['php -v', $descriptor, &$pipes]);
        expect($process)->to->satisfy('is_resource', 'should be resource');
        expect($pipes)->not->to->be->empty;
        proc_close($process);
    });
});
