<?php

declare(strict_types=1);

namespace BenMorel\XMLStreamer\Tests;

use BenMorel\XMLStreamer\XMLStreamer;
use BenMorel\XMLStreamer\XMLStreamerException;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for class XMLStreamer.
 */
class XMLStreamerTest extends TestCase
{
    /**
     * @dataProvider providerStream
     *
     * @param string   $xmlFile   The XML file name.
     * @param int|null $maxNodes  The maximum number of nodes to stream, or null for no maximum.
     * @param string[] $nodeNames The node names to construct the XMLStreamer with.
     * @param array    $nodes     The expected node contents.
     *
     * @return void
     */
    public function testStream(string $xmlFile, ?int $maxNodes, array $nodeNames, array $nodes) : void
    {
        $xmlFile = $this->getFileName($xmlFile);

        $streamedNodes = [];

        $streamer = new XMLStreamer(...$nodeNames);

        if ($maxNodes !== null) {
            $streamer->setMaxNodes($maxNodes);
        }

        $nodeCount = $streamer->stream($xmlFile, function(\DOMElement $element) use ($nodeNames, & $streamedNodes) {
            $this->assertSame(end($nodeNames), $element->nodeName);

            $document = new \DOMDocument();
            $document->appendChild($element);
            $simpleXmlElement = simplexml_import_dom($element);
            $streamedNodes[] = $this->convertSimpleXMLElement($simpleXmlElement);
        });

        $this->assertSame($nodes, $streamedNodes);
        $this->assertCount($nodeCount, $streamedNodes);
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

            // max nodes
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

        $streamer->stream($xmlFile, function(\DOMElement $element) use (& $productName) {
            $document = new \DOMDocument();
            $document->appendChild($element);
            $element = simplexml_import_dom($element);
            $productName = (string) $element->name;
        });

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
        $streamer->stream($xmlFile, function() {});
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
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Missing node names.
     *
     * @return void
     */
    public function testConstructorWithNoParameters() : void
    {
        new XMLStreamer();
    }

    /**
     * @dataProvider providerSetMaxNodesWithInvalidNumber
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Max nodes cannot be less than 1.
     *
     * @param int $maxNodes
     *
     * @return void
     */
    public function testSetMaxNodesWithInvalidNumber(int $maxNodes) : void
    {
        $streamer = new XMLStreamer('a', 'b');
        $streamer->setMaxNodes($maxNodes);
    }

    /**
     * @return array
     */
    public function providerSetMaxNodesWithInvalidNumber() : array
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
