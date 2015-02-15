<?php
$token = getenv('PERIDOT_TEST_TOKEN');

/**
 * This test suite will only run when a concurrency token is available.
 */
if ($token) {
    describe('test token', function () use ($token) {
        it('should exist', function () use ($token) {
            expect($token)->to->not->be->empty;
        });
    });
}
