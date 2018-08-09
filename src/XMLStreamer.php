<?php

declare(strict_types=1);

namespace BenMorel\XMLStreamer;

/**
 * Streams an XML file, calling a callback function with a DOM node.
 */
class XMLStreamer
{
    /**
     * The hierarchy of names of the nodes to stream, starting at the root node.
     *
     * @var string[]
     */
    private $nodeNames;

    /**
     * The depth of the nodes to stream.
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
     * XMLStreamer constructor.
     *
     * @param string ...$nodeNames The hierarchy of names of the nodes to stream.
     */
    public function __construct(string ...$nodeNames)
    {
        $this->nodeNames = $nodeNames;
        $this->depth = count($nodeNames) - 1;

        $this->errorHandler = function($severity, $message) {
            throw new XMLStreamerException($message);
        };
    }

    /**
     * Streams an XML file.
     *
     * @param string   $file     The XML file path.
     * @param callable $callback A function that will be called with each DOMNode object.
     *
     * @return int The number of nodes streamed.
     *
     * @throws XMLStreamerException If an error occurs at any point, before or after the streaming has started.
     */
    public function stream(string $file, callable $callback) : int
    {
        $nodeCount = 0;
        $xmlReader = new \XMLReader();

        $this->open($xmlReader, $file);

        for (;;) {
            if ($xmlReader->nodeType === \XMLReader::ELEMENT) {
                if ($xmlReader->name !== $this->nodeNames[$xmlReader->depth]) {
                    if (! $this->next($xmlReader)) {
                        break;
                    }

                    continue;
                }

                if ($xmlReader->depth === $this->depth) {
                    $domNode = $this->expand($xmlReader);
                    $callback($domNode);
                    $nodeCount++;

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

        return $nodeCount;
    }

    /**
     * Runs XMLReader::open(), catching errors and throwing them as exceptions.
     *
     * @param \XMLReader $xmlReader
     *
     * @param string $file
     *
     * @throws XMLStreamerException
     */
    private function open(\XMLReader $xmlReader, string $file) : void
    {
        set_error_handler($this->errorHandler);

        try {
            $xmlReader->open($file);
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
