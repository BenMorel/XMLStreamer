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

This library is under development.

The current releases are numbered `0.x.y`. When a non-breaking change is introduced (adding new methods, optimizing
existing code, etc.), `y` is incremented.

**When a breaking change is introduced, a new `0.x` version cycle is always started.**

It is therefore safe to lock your project to a given release cycle, such as `0.1.*`.

If you need to upgrade to a newer release cycle, check the [release history](https://github.com/BenMorel/XMLStreamer/releases)
for a list of changes introduced by each further `0.x.0` version.

## Quickstart

Let's say you have a product feed containing a list of one million products, in the following format:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<feed>
    <products>
        <product>
            <id>1</id>
            <name>foo</name>
            ...
        </product>
    
        ...
    
        <product>
            <id>1000000</id>
            <name>bar</name>
            ...
        </product>
    </products>
</feed>
```

To read it product by product, you instantiate an `XMLStreamer` with the path to a `<product>`:

```php
use BenMorel\XMLStreamer\XMLStreamer;

$streamer = new XMLStreamer('feed', 'products', 'product');
```

Any element in the document that does not match this path will be ignored.

You can then proceed to streaming the file to a callback function, that will receive a [DOMNode](http://php.net/manual/en/class.domnode.php) object for each `<product>` element:

```php
$streamer->stream('product-feed.xml', function(\DOMNode $product) {
    // ...
});
```

You can query this `DOMNode` directly, or wrap it in a [DOMDocument](http://php.net/manual/en/class.domdocument.php) for more possibilities:

```php
$streamer->stream('product-feed.xml', function(\DOMNode $product) {
    $document = new \DOMDocument();
    $document->appendChild($product);

    echo $document->getElementsByTagName('name')->item(0)->textContent; // foo, ..., bar
});
```

If you prefer to work with SimpleXML, you can use [simplexml_import_dom()](http://php.net/manual/en/function.simplexml-import-dom.php). SimpleXML also requires that you wrap your node in a `DOMDocument` before importing it:

```php
$streamer->stream('product-feed.xml', function(\DOMNode $product) {
    $document = new \DOMDocument();
    $document->appendChild($product);
    $element = simplexml_import_dom($product);

    echo $element->name; // foo, ..., bar
});
```

### Return value

If the document is valid, after all nodes have been processed, `stream()` returns the number of imported nodes.

## Error handling

If an error occurs at any point (error while reading the file, or malformed document), an exception is thrown.

Note that the streaming may have already been started when the exception is thrown, so your callback function may have already been called with a number of nodes.
