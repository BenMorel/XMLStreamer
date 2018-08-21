<?php

declare(strict_types=1);

namespace BenMorel\XMLStreamer;

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
    private $elementNames;

    /**
     * The depth of the elements to stream.
     *
     * @var int
     */
    private $depth;

    /**
     * The transient error handler used to catch PHP errors in XMLReader.
     *
     * @var \Closure
     */
    private $errorHandler;

    /**
     * The maximum number of elements to stream. Optional.
     *
     * @var int|null
     */
    private $maxElements;

    /**
     * The encoding of the file, if missing from the XML declaration, or to override it. Optional.
     *
     * @var string|null
     */
    private $encoding;

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

        $this->errorHandler = function($severity, $message) {
            throw new XMLStreamerException($message);
        };
    }

    /**
     * Sets the maximum number of elements to stream.
     *
     * This can be useful to get a preview of an XML file.
     *
     * @param int $maxElements
     *
     * @return void
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
     *
     * @param string|null $encoding
     *
     * @return void
     */
    public function setEncoding(?string $encoding) : void
    {
        $this->encoding = $encoding;
    }

    /**
     * Streams an XML file.
     *
     * @param string   $file     The XML file path.
     * @param callable $callback A function that will be called with each DOMElement object.
     *
     * @return int The number of elements streamed.
     *
     * @throws XMLStreamerException If an error occurs at any point, before or after the streaming has started.
     */
    public function stream(string $file, callable $callback) : int
    {
        $elementCount = 0;
        $xmlReader = new \XMLReader();

        $this->open($xmlReader, $file);

        for (;;) {
            if ($xmlReader->nodeType === \XMLReader::ELEMENT) {
                if ($xmlReader->name !== $this->elementNames[$xmlReader->depth]) {
                    if (! $this->next($xmlReader)) {
                        break;
                    }

                    continue;
                }

                if ($xmlReader->depth === $this->depth) {
                    $domElement = $this->expand($xmlReader);
                    $callback($domElement);
                    $elementCount++;

                    if ($elementCount === $this->maxElements) {
                        break;
                    }

                    if (! $this->next($xmlReader)) {
                        break;
                    }

                    continue;
                }
            }

            if (! $this->read($xmlReader)) {
                break;
            }
        }

        $xmlReader->close();

        return $elementCount;
    }

    /**
     * Runs XMLReader::open(), catching errors and throwing them as exceptions.
     *
     * @param \XMLReader $xmlReader
     * @param string     $file
     *
     * @throws XMLStreamerException
     */
    private function open(\XMLReader $xmlReader, string $file) : void
    {
        set_error_handler($this->errorHandler);

        try {
            $xmlReader->open($file, $this->encoding);
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Runs XMLReader::expand(), catching errors and throwing them as exceptions.
     *
     * @param \XMLReader $xmlReader
     *
     * @return \DOMNode
     *
     * @throws XMLStreamerException
     */
    private function expand(\XMLReader $xmlReader) : \DOMNode
    {
        set_error_handler($this->errorHandler);

        try {
            $result = $xmlReader->expand();
        } finally {
            restore_error_handler();
        }

        return $result;
    }

    /**
     * Runs XMLReader::read(), catching errors and throwing them as exceptions.
     *
     * @param \XMLReader $xmlReader
     *
     * @return bool
     *
     * @throws XMLStreamerException
     */
    private function read(\XMLReader $xmlReader) : bool
    {
        set_error_handler($this->errorHandler);

        try {
            $result = $xmlReader->read();
        } finally {
            restore_error_handler();
        }

        return $result;
    }

    /**
     * Runs XMLReader::next(), catching errors and throwing them as exceptions.
     *
     * @param \XMLReader $xmlReader
     *
     * @return bool
     *
     * @throws XMLStreamerException
     */
    private function next(\XMLReader $xmlReader) : bool
    {
        set_error_handler($this->errorHandler);

        try {
            $result = $xmlReader->next();
        } finally {
            restore_error_handler();
        }

        return $result;
    }
}
