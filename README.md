# XML Streamer

Stream large XML files as individual DOM nodes with low memory consumption.

[![Build Status](https://secure.travis-ci.org/BenMorel/XMLStreamer.svg?branch=master)](http://travis-ci.org/BenMorel/XMLStreamer)
[![Coverage Status](https://coveralls.io/repos/BenMorel/XMLStreamer/badge.svg?branch=master)](https://coveralls.io/r/BenMorel/XMLStreamer?branch=master)
[![Latest Stable Version](https://poser.pugx.org/benmorel/xml-streamer/v/stable)](https://packagist.org/packages/benmorel/xml-streamer)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](http://opensource.org/licenses/MIT)

## Installation

This library is installable via [Composer](https://getcomposer.org/):

```bash
composer require benmorel/xml-streamer
```

## Requirements

This library requires:

- PHP 7.1 or later
- the [DOM](http://php.net/manual/en/book.dom.php) extension
- the [XMLReader](http://php.net/manual/en/book.xmlreader.php) extension

These extensions are enabled by default, and should be available in most PHP environments.

## Project status & release process

This library is still under development.

The current releases are numbered `0.x.y`. When a non-breaking change is introduced (adding new methods, optimizing
existing code, etc.), `y` is incremented.

**When a breaking change is introduced, a new `0.x` version cycle is always started.**

It is therefore safe to lock your project to a given release cycle, such as `0.1.*`.

If you need to upgrade to a newer release cycle, check the [release history](https://github.com/BenMorel/XMLStreamer/releases)
for a list of changes introduced by each further `0.x.0` version.

## Quickstart

To be written.
