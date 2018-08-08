<?php

declare(strict_types=1);

namespace BenMorel\XMLStreamer\Tests;

use BenMorel\XMLStreamer\XMLStreamer;
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
     * @param string[] $nodeNames The node names to construct the XMLStreamer with.
     * @param array    $nodes     The expected node contents.
     *
     * @return void
     */
    public function testStream(string $xmlFile, array $nodeNames, array $nodes) : void
    {
        $xmlFile = $this->getFileName($xmlFile);

        $streamedNodes = [];

        $streamer = new XMLStreamer(...$nodeNames);
        $streamer->stream($xmlFile, function(\DOMDocument $document) use ($nodeNames, & $streamedNodes) {
            $this->assertSame(end($nodeNames), $document->documentElement->nodeName);

            $simpleXmlElement = simplexml_import_dom($document);
            $streamedNodes[] = $this->convertSimpleXMLElement($simpleXmlElement);
        });

        $this->assertSame($nodes, $streamedNodes);
    }

    /**
     * @return array
     */
    public function providerStream() : array
    {
        return [
            ['products-empty.xml', ['products', 'product'], []],

            ['products-depth-1.xml', ['products', 'product'], [
                ['id' => '1', 'name' => 'foo'],
                ['id' => '2', 'name' => 'bar'],
            ]],

            ['products-depth-2.xml', ['root', 'products', 'product'], [
                ['id' => '1', 'name' => 'foo'],
                ['id' => '2', 'name' => 'bar'],
                ['id' => '3', 'name' => 'baz'],
            ]],

            ['products-depth-1.xml', ['root', 'product'], []],
            ['products-depth-1.xml', ['products', 'item'], []],

            ['products-depth-2.xml', ['products', 'product'], []],
            ['products-depth-2.xml', ['root', 'product'], []],
            ['products-depth-2.xml', ['root', 'products', 'item'], []],
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
    public function testStreamInvalidDocument(string $xmlFile, $x, string $expectedMessage) : void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        $xmlFile = $this->getFileName($xmlFile);

        $streamer = new XMLStreamer('a', 'b');
        $streamer->stream($xmlFile, function() {});
    }

    /**
     * @todo no xml declaration
     * @todo broken child
     *
     * @return array
     */
    public function providerStreamInvalidDocument() : array
    {
        return [
            ['empty.xml', ['parent', 'child'], 'parser error : Extra content at the end of the document'],
            ['no-root.xml', ['parent', 'child'], 'parser error : Extra content at the end of the document'],

            ['unclosed-root-no-contents.xml', ['root', 'item'], 'parser error : Extra content at the end of the document'],

            ['unclosed-root-with-contents.xml', ['products', 'product'], 'parser error : Extra content at the end of the document'],

            ['products-unclosed-element.xml', ['products', 'product'], 'parser error : Opening and ending tag mismatch'],
        ];
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unable to open source data
     */
    public function testStreamNonexistentFile()
    {
        $xmlFile = $this->getFileName('nonexistent.xml');

        $streamer = new XMLStreamer('products', 'product');
        $streamer->stream($xmlFile, function() {});
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
