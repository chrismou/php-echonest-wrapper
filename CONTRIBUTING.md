CONTRIBUTING
============

We always appreciate contributions, and will accept pull requests as long as they're following the guidelines listed below.  In some cases we may make an exception - if you're struggling, 
drop me an email to the address listed in the [composer.json](composer.json) file and we can discuss it further.

## Guidelines

* Please follow the [PSR-2 Coding Standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md) and [PHP-FIG Naming Conventions](https://github.com/php-fig/fig-standards/blob/master/bylaws/002-psr-naming-conventions.md).
* Ensure that the current tests pass - instructions are below. If you add something new, please add new tests where relevant.
* Keep a meaningful git history - we're going to be checking over pull requests, and it'd help a lot if we can see at a glance what each commit relates to. If you had to make multiple minor commits while developing, please [squash](http://git-scm.com/book/en/Git-Tools-Rewriting-History) them before submitting.
* Please [rebase](http://git-scm.com/book/en/Git-Branching-Rebasing) where possible.


## Running Tests

Assuming you've already installed [Composer](https://getcomposer.org)...

First, install the dependencies:

```bash
$ composer install
```

Then run phpunit:

```bash
$ vendor/bin/phpunit
```

If you use docker, you can also run the test suite against all supported PHP versions:

```bash
./vendor/bin/dunit
```

Once you've made the pull request, the tests will automatically be run again by [Travis CI](https://travis-ci.org/) on all supported php versions.