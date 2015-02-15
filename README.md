# peridot-concurrency

Lets run our specs a lot faster!

[![Build Status](https://travis-ci.org/peridot-php/peridot-concurrency.png)](https://travis-ci.org/peridot-php/peridot-concurrency) [![HHVM Status](http://hhvm.h4cc.de/badge/peridot-php/peridot-concurrency.svg)](http://hhvm.h4cc.de/package/peridot-php/peridot-concurrency)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/peridot-php/peridot-concurrency/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/peridot-php/peridot-concurrency/?branch=master)

This plugin includes a runner and reporter that allows you to run your peridot tests concurrently! Have a bunch of slow WebDriver tests? How about a bunch of DB tests? Tired of waiting? Why not run them at the same time!?!?

## How does it work?

peridot-concurrency leverages good old fashioned non-blocking IO to run our tests concurrently. This plugin creates `N` worker processes as specified on the CLI, and each worker communicates with the main process as test results become available. The function responsible for polling workers is the [stream_select](http://php.net/manual/en/function.stream-select.php) function.

## Usage

peridot-concurreny can be added to your test workflow like any Peridot extension - that is via the `peridot.php` file:

```php
use Evenement\EventEmitterInterface;
use Peridot\Concurrency\ConcurrencyPlugin;

return function (EventEmitterInterface $emitter) {
    $concurrency = new ConcurrencyPlugin($emitter);
};
```

After registering the plugin, your usage screen should have a couple new options:

![Peridot concurrency usage](https://raw.github.com/peridot-php/peridot-concurrency/master/usage.png "Peridot concurrency usage")

### --concurrent

This is how you signal Peridot to run your tests concurrently. This will start as many worker processes as specified by the new `-p` option (defaulting to 5). In addition to starting workers, this will override the Peridot reporter to use the `ConcurrentReporter` provided by this plugin. This reporter prevents output from being garbled, and offers useful information like the time it takes each suite file to run.

### --processes (-p)

This new option can be used to specify the number of worker processes to start. It defaults to 5, and the sweet spot will vary from machine to machine.
