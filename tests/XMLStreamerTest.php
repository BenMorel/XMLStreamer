<?php

declare(strict_types=1);

namespace BenMorel\XMLStreamer\Tests;

use BenMorel\XMLStreamer\XMLStreamer;
use BenMorel\XMLStreamer\XMLStreamerException;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for class XMLStreamer.
 */
class XMLStreamerTest extends TestCase
{
    /**
     * @dataProvider providerStream
     *
     * @param string   $xmlFile      The XML file name.
     * @param int|null $maxElements  The maximum number of elements to stream, or null for no maximum.
     * @param string[] $elementNames The element names to construct the XMLStreamer with.
     * @param array    $elements     The expected element contents.
     *
     * @return void
     */
    public function testStream(string $xmlFile, ?int $maxElements, array $elementNames, array $elements) : void
    {
        $xmlFile = $this->getFileName($xmlFile);

        $streamedElements = [];

        $streamer = new XMLStreamer(...$elementNames);

        if ($maxElements !== null) {
            $streamer->setMaxElements($maxElements);
        }

        foreach ($generator = $streamer->stream($xmlFile) as $element) {
            $this->assertSame(end($elementNames), $element->nodeName);

            $document = new \DOMDocument();
            $document->appendChild($element);
            $simpleXmlElement = simplexml_import_dom($element);
            $streamedElements[] = $this->convertSimpleXMLElement($simpleXmlElement);
        }

        $elementCount = $generator->getReturn();

        $this->assertSame($elements, $streamedElements);
        $this->assertCount($elementCount, $streamedElements);
    }

    /**
     * @return array
     */
    public function providerStream() : array
    {
        return [
            ['products-empty.xml', null, ['products', 'product'], []],

            ['products-depth-0.xml', null, ['product'], [
                ['id' => '1', 'name' => 'foo'],
            ]],

            ['products-depth-1.xml', null, ['products', 'product'], [
                ['id' => '1', 'name' => 'foo'],
                ['id' => '2', 'name' => 'bar'],
            ]],

            ['products-depth-2.xml', null, ['root', 'products', 'product'], [
                ['id' => '1', 'name' => 'foo'],
                ['id' => '2', 'name' => 'bar'],
                ['id' => '3', 'name' => 'baz'],
            ]],

            ['products-depth-2.xml', null, ['root', 'discontinued-products', 'product'], [
                ['id' => '1234', 'name' => 'oldie'],
            ]],

            ['products-depth-0.xml', null, ['root'], []],

            ['products-depth-1.xml', null, ['root', 'product'], []],
            ['products-depth-1.xml', null, ['products', 'item'], []],

            ['products-depth-2.xml', null, ['products', 'product'], []],
            ['products-depth-2.xml', null, ['root', 'product'], []],
            ['products-depth-2.xml', null, ['root', 'products', 'item'], []],

            // max elements
            ['products-depth-2.xml', 1, ['root', 'products', 'product'], [
                ['id' => '1', 'name' => 'foo'],
            ]],
            ['products-depth-2.xml', 2, ['root', 'products', 'product'], [
                ['id' => '1', 'name' => 'foo'],
                ['id' => '2', 'name' => 'bar'],
            ]],
            ['products-depth-2.xml', 3, ['root', 'products', 'product'], [
                ['id' => '1', 'name' => 'foo'],
                ['id' => '2', 'name' => 'bar'],
                ['id' => '3', 'name' => 'baz'],
            ]],
            ['products-depth-2.xml', 4, ['root', 'products', 'product'], [
                ['id' => '1', 'name' => 'foo'],
                ['id' => '2', 'name' => 'bar'],
                ['id' => '3', 'name' => 'baz'],
            ]],
        ];
    }

    /**
     * @dataProvider providerSetEncoding
     *
     * @param string      $xmlFile
     * @param string|null $setEncoding
     * @param string|null $expectedExceptionMessage
     *
     * @return void
     */
    public function testSetEncoding(string $xmlFile, ?string $setEncoding, ?string $expectedExceptionMessage) : void
    {
        $xmlFile = $this->getFileName($xmlFile);

        $streamer = new XMLStreamer('products', 'product');
        $streamer->setEncoding($setEncoding);

        if ($expectedExceptionMessage !== null) {
            $this->expectException(XMLStreamerException::class);
            $this->expectExceptionMessage($expectedExceptionMessage);
        }

        $productName = null;

        foreach ($streamer->stream($xmlFile) as $element) {
            $document = new \DOMDocument();
            $document->appendChild($element);
            $element = simplexml_import_dom($element);
            $productName = (string) $element->name;
        }

        if ($expectedExceptionMessage === null) {
            $this->assertSame('äëïöü', $productName);
        }
    }

    /**
     * @return array
     */
    public function providerSetEncoding() : array
    {
        return [
            ['iso-8859-1.xml', null, null],
            ['iso-8859-1.xml', 'UTF-8', null], // overriding does nothing here
            ['iso-8859-1-no-encoding.xml', null, 'parser error : Input is not proper UTF-8, indicate encoding !'],
            ['iso-8859-1-no-encoding.xml', 'ISO-8859-1', null],
        ];
    }

    /**
     * @dataProvider providerStreamInvalidDocument
     *
     * @param string $xmlFile
     * @param string $expectedMessage
     *
     * @return void
     */
    public function testStreamInvalidDocument(string $xmlFile, string $expectedMessage) : void
    {
        $this->expectException(XMLStreamerException::class);
        $this->expectExceptionMessage($expectedMessage);

        $xmlFile = $this->getFileName($xmlFile);

        $streamer = new XMLStreamer('a', 'b');
        foreach ($streamer->stream($xmlFile) as $element);
    }

    /**
     * @return array
     */
    public function providerStreamInvalidDocument() : array
    {
        return [
            ['nonexistent.xml', 'Unable to open source data'],
            ['empty.xml', 'parser error : Extra content at the end of the document'],
            ['no-root.xml', 'parser error : Extra content at the end of the document'],

            ['unclosed-root-no-contents.xml', 'parser error : Extra content at the end of the document'],
            ['unclosed-root-with-contents.xml', 'parser error : Extra content at the end of the document'],

            ['products-unclosed-element.xml', 'parser error : Opening and ending tag mismatch'],
            ['products-invalid-entity.xml', 'parser error : xmlParseEntityRef: no name'],
        ];
    }

    /**
     * @return void
     */
    public function testConstructorWithNoParameters() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing element names.');

        new XMLStreamer();
    }

    /**
     * @dataProvider providerSetMaxElementsWithInvalidNumber
     *
     * @param int $maxElements
     *
     * @return void
     */
    public function testSetMaxElementsWithInvalidNumber(int $maxElements) : void
    {
        $streamer = new XMLStreamer('a', 'b');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Max elements cannot be less than 1.');
        $streamer->setMaxElements($maxElements);
    }

    /**
     * @return array
     */
    public function providerSetMaxElementsWithInvalidNumber() : array
    {
        return [
            [-2],
            [-1],
            [0]
        ];
    }

    /**
     * @param string $xmlFile
     *
     * @return string
     */
    private function getFileName(string $xmlFile) : string
    {
        return __DIR__ . '/xml/' . $xmlFile;
    }

    /**
     * @param \SimpleXMLElement $element
     *
     * @return array|string
     */
    private function convertSimpleXMLElement(\SimpleXMLElement $element)
    {
        if (! $element->count()) {
            return (string) $element;
        }

        $result = [];

        foreach ($element->children() as $child) {
            $result[$child->getName()] = $this->convertSimpleXMLElement($child);
        }

        return $result;
    }
}
