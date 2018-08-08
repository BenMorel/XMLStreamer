<?php

declare(strict_types=1);

namespace BenMorel\XMLStreamer;

/**
 * Streams an XML file, calling a callback function with a DOM node.
 */
class XMLStreamer
{
    /**
     * @var string[]
     */
    private $nodeNames;

    /**
     * @var int
     */
    private $depth;

    /**
     * @var \Closure
     */
    private $errorHandler;

    /**
     * XMLStreamer constructor.
     *
     * @param string ...$nodeNames The node names that lead to the streamable nodes.
     */
    public function __construct(string ...$nodeNames)
    {
        $this->nodeNames = $nodeNames;
        $this->depth = count($nodeNames) - 1;

        $this->errorHandler = function($severity, $message) {
            throw new \RuntimeException($message);
        };
    }

    /**
     * @param string   $file
     * @param callable $callback
     *
     * @return int The number of nodes streamed.
     *
     * @throws \RuntimeException If an error occurs.
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

                    $document = new \DOMDocument();
                    $document->appendChild($domNode);
                    $callback($document);
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
     * @param \XMLReader $xmlReader
     *
     * @param string $file
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
