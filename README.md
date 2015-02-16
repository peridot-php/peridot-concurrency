# peridot-concurrency

Lets run our specs a lot faster!

[![Build Status](https://travis-ci.org/peridot-php/peridot-concurrency.png)](https://travis-ci.org/peridot-php/peridot-concurrency) [![HHVM Status](http://hhvm.h4cc.de/badge/peridot-php/peridot-concurrency.svg)](http://hhvm.h4cc.de/package/peridot-php/peridot-concurrency)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/peridot-php/peridot-concurrency/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/peridot-php/peridot-concurrency/?branch=master) [![Coverage Status](https://coveralls.io/repos/peridot-php/peridot-concurrency/badge.svg?branch=master)](https://coveralls.io/r/peridot-php/peridot-concurrency?branch=master)

This plugin includes a runner and reporter that allows you to run your Peridot tests concurrently! Have a bunch of slow WebDriver tests? How about a bunch of DB tests? Tired of waiting? Why not run them at the same time!?!?

## How does it work?

peridot-concurrency leverages good old fashioned non-blocking IO to run our tests concurrently. This plugin creates `N` worker processes as specified on the command line, and each worker communicates with the main process as test results become available. The function responsible for polling workers is the [stream_select](http://php.net/manual/en/function.stream-select.php) function.

## Usage

You can install via composer:

```
composer require --dev peridot-php/peridot-concurrency
```

peridot-concurreny can be added to your test workflow like any Peridot extension - that is via the `peridot.php` file:

```php
use Evenement\EventEmitterInterface;
use Peridot\Concurrency\ConcurrencyPlugin;

return function (EventEmitterInterface $emitter) {
    $concurrency = new ConcurrencyPlugin($emitter);
};
```

After registering the plugin, your usage screen should have a couple of new options:

![Peridot concurrency usage](https://raw.github.com/peridot-php/peridot-concurrency/master/usage.png "Peridot concurrency usage")

### --concurrent

This is how you signal Peridot to run your tests concurrently. This will start as many worker processes as specified by the new `-p` option (defaulting to 5). In addition to starting workers, this will override the Peridot reporter to use the `ConcurrentReporter` provided by this plugin. This reporter prevents output from being garbled, and offers useful information like the time it takes each suite file to run.

### --processes (-p)

This new option can be used to specify the number of worker processes to start. It defaults to 5, and the sweet spot will vary from machine to machine.

## OS Support

Due to limitations of the Windows operating system, it is currently not supported by the peridot-concurreny plugin. This has to do with the `stream_select` function not being able to work with file descriptors returned from `proc_open` on the Windows operating system.

`stream_select` is currently the most efficient "out-of-the-box" solution for this type of work, but stay tuned for a "process per test" runner that works on Windows, and a [pthreads](http://php.net/manual/en/book.pthreads.php) based runner where the extension is available.

## Performance

There is some overhead with creating processes, so not every suite will improve from using concurrency. For example, if you have a suite that runs in 68ms, it's hard to improve on that. BUT! The test suites run so far, have noticed significant speed improvements, even on the unit level.

peridot-concurrency runs it's own tests concurrently:

### before

![Peridot concurrency suite run serially](https://raw.github.com/peridot-php/peridot-concurrency/master/dot.png "Peridot concurrency suite run serially")

### after

![Peridot concurrency suite run concurrently](https://raw.github.com/peridot-php/peridot-concurrency/master/concurrent.png "Peridot concurrency suite run concurrently")

On the machines tested, Peridot's own test suite was run in 1/4th of the time! You can see peridot-concurrency's [travis build](https://travis-ci.org/peridot-php/peridot-concurrency) to see even faster run times.

The thing to note here is that these are just unit test suites. The amount of time saved running a bulky integration or functional test suite would be even more significant (examples coming soon).

### Fine tuning

As mentioned before, the exact number of processes to use will vary from machine to machine. Try experimenting with different process numbers to get the most speed out of peridot-concurrency.

## Test Tokens

Each worker process has its own unique ID, and it is surfaced as an environment variable `PERIDOT_TEST_TOKEN`. This may be useful for creating unique resources based on this token. For instance:

```php
$id = getenv('PERIDOT_TEST_TOKEN');
$dbname = "mydb_$id";
//create and seed DB identified by $dbname
//do database things
```

## Contributing

Concurrency can be tricky business. If you have any issues or ideas please let us know! Pull requests are always welcome.
