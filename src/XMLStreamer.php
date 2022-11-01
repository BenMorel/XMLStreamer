<?php

declare(strict_types=1);

namespace BenMorel\XMLStreamer;

use DOMElement;
use Generator;

/**
 * Streams an XML file, calling a callback function with a DOM element.
 */
class XMLStreamer
{
    /**
     * The hierarchy of names of the elements to stream, starting at the root element.
     *
     * @var string[]
     */
    private array $elementNames;

    /**
     * The depth of the elements to stream.
     */
    private int $depth;

    /**
     * The maximum number of elements to stream. Optional.
     */
    private ?int $maxElements = null;

    /**
     * The encoding of the file, if missing from the XML declaration, or to override it. Optional.
     */
    private ?string $encoding = null;

    /**
     * XMLStreamer constructor.
     *
     * @param string ...$elementNames The hierarchy of names of the elements to stream.
     *
     * @throws \InvalidArgumentException If no element names are given.
     */
    public function __construct(string ...$elementNames)
    {
        if (count($elementNames) === 0) {
            throw new \InvalidArgumentException('Missing element names.');
        }

        $this->elementNames = $elementNames;
        $this->depth = count($elementNames) - 1;
    }

    /**
     * Sets the maximum number of elements to stream.
     *
     * This can be useful to get a preview of an XML file.
     *
     * @throws \InvalidArgumentException
     */
    public function setMaxElements(int $maxElements) : void
    {
        if ($maxElements < 1) {
            throw new \InvalidArgumentException('Max elements cannot be less than 1.');
        }

        $this->maxElements = $maxElements;
    }

    /**
     * Sets the encoding of the file.
     *
     * This can be used if the encoding is missing from the XML declaration, or to override it.
     */
    public function setEncoding(?string $encoding) : void
    {
        $this->encoding = $encoding;
    }

    /**
     * Streams an XML file.
     *
     * @param string $file The XML file path.
     *
     * @return Generator<DOMElement> The streamed elements. The generator returns the number of elements streamed.
     *
     * @throws XMLReaderException If an error occurs at any point, before or after the streaming has started.
     */
    public function stream(string $file) : Generator
    {
        $elementCount = 0;
        $xmlReader = new XMLReader();

        $xmlReader->open($file, $this->encoding);

        for (;;) {
            if ($xmlReader->nodeType() === \XMLReader::ELEMENT) {
                if ($xmlReader->name() !== $this->elementNames[$xmlReader->depth()]) {
                    if (! $xmlReader->next()) {
                        break;
                    }

                    continue;
                }

                if ($xmlReader->depth() === $this->depth) {
                    /** @var DOMElement $domElement */
                    $domElement = $xmlReader->expand();

                    yield $domElement;

                    if (++$elementCount === $this->maxElements) {
                        break;
                    }

                    if (! $xmlReader->next()) {
                        break;
                    }

                    continue;
                }
            }

            if (! $xmlReader->read()) {
                break;
            }
        }

        $xmlReader->close();

        return $elementCount;
    }
}
