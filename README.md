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

Docs to follow

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
