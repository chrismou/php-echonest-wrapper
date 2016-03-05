# PHP Echonest API Wrapper

[![Build Status](https://travis-ci.org/chrismou/php-echonest-wrapper.svg?branch=master)](https://travis-ci.org/chrismou/php-echonest-wrapper)
[![Test Coverage](https://codeclimate.com/github/chrismou/php-echonest-wrapper/badges/coverage.svg)](https://codeclimate.com/github/chrismou/php-echonest-wrapper/coverage)
[![Code Climate](https://codeclimate.com/github/chrismou/php-echonest-wrapper/badges/gpa.svg)](https://codeclimate.com/github/chrismou/php-echonest-wrapper)

A dead simple wrapper class for the echonest API.

Includes options for max number of attempts before giving up, and a auto rate limiter, which spaces out requests based 
on the number of API requests remaining for that minute (which is included in the echonest response headers).

## Install

For [composer](http://getcomposer.org) based projects:

```
composer require chrismou/echonest
```

## Usage

First you need an Echonest API key.  You can obtain one by signing up here: [https://developer.echonest.com/account/register](https://developer.echonest.com/account/register)

To set up the echonest API client:

```
$lastfm = new \Chrismou\Echonest(
    new GuzzleHttp\Client(),
    YOUR_API_KEY
);
```

(you can also pass a PSR-7 compliant logger as a third argument - [more details below](#logging))

The format for calls is: `$echonest->query($resource, $method, $parameters, $autoRateLimit, $maxRetries);`, where:

* **resource** is the specific Echonest resource you're querying (ie, 'artist', 'genre', 'song')
* **method** is the method specific to the resource you're calling (ie, 'search', 'profile', 'images')
* **parameters** (optional) are the are the parameters specified in the [API documentation](http://developer.echonest.com/docs/v4) for that endpoint.
* **autoRateLimit** (optional) whether to let the wrapper manage rate limiting ([see below](#rate-limiting))
* **maxRetries** (optional) how many times to attempt a request before giving up

So, if you wanted to get all images for Cher, you could run:

```
$echonest->query('artist', 'images', ['name' => 'cher']);
```

Or if you wanted Artist by a specific genre, you could run:

```
$echonest->query('genre', 'artists', ['name' => 'rock']);
```

You can also specify 'buckets' as a way of returning multiple sets of data within the same API query.  To request them in the request, 
you can do the following:

```
$echonest->query(
    'artist',
    'search',
    [
        'name' => 'Arctic Monkeys',
        'bucket' => [
            'genre',
            'biographies',
            'familiarity',
            'images'
        ]
    ]
);
```

Refer to the [Echonest API documentation](http://developer.echonest.com/docs/v4) for a full list of available endpoints, parameters, buckets
and example responses. This wrapper is designed to support virtually all endpoints out of the box, so you should be safe to 
use whichever ones you need.

## Rate limiting
Echonest implements rate limiting, so if you make too many requests within a minute it'll stop you connecting until the minute is up
and your limit is reset (the number of requests you get depends on your Echonest account type - if you're in need of more than the default, 
[drop them an email](http://the.echonest.com/contact/)).

This wrapper supports auto rate limiting at the client end, thanks to Echonest returning the number of requests left in the response headers. It does
this by checking the number left after each request and the amount of time you have to make these requests, and calculates a suitable wait time
between each request.  In essence, it tries to space the available requests out over the minute, rather than pounding the API over 20 seconds and 
then sitting dormant for 40 seconds waiting for the reset.
 
In some cases, you may want to override this (say, if you know you're only making a total of 20 requests and would rather just run them ASAP rather 
than space them over 1 minute), which you can do by specifying ```false``` as the 4 parameter in the method call.

For example:

```
$echonest->query('artist', 'images', ['name' => 'cher'], false);
```

## Logging
Optionally, you can pass a logger as the third constructor argument to the client, as long as it implements the [\Psr\Log](https://github.com/php-fig/log) interface 
(ie, monolog).  By passing this in, some basic logging will automatically be enabled, logging any errors connecting to Echonest and the reasons (if we have one).

The echonest client assumes the logger has already been properly configured, so you'll need to do this before passing it in.  For more information on 
configuring Monolog for use with this class, see [the usage documentation](https://github.com/Seldaek/monolog/blob/master/doc/01-usage.md#configuring-a-logger).

## Tests

To run the unit test suite:

```
curl -s https://getcomposer.org/installer | php
php composer.phar install
./vendor/bin/phpunit
```

If you use docker, you can also run the test suite against all supported PHP versions:
```
./vendor/bin/dunit
```

## License

Released under the MIT License. See [LICENSE](LICENSE.md).
