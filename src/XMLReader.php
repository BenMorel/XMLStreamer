<?php

declare(strict_types=1);

namespace BenMorel\XMLStreamer;

/**
 * Wrapper for the native XMLReader, that throws exceptions instead of triggering PHP errors.
 */
final class XMLReader
{
    /**
     * The wrapped native XMLReader instance.
     */
    private \XMLReader $xmlReader;

    /**
     * The transient error handler used to catch PHP errors triggered in the native XMLReader class.
     */
    private \Closure $errorHandler;

    /**
     * XMLReader constructor.
     */
    public function __construct()
    {
        $this->xmlReader = new \XMLReader();

        /** @psalm-suppress UnusedClosureParam */
        $this->errorHandler = static function(int $severity, string $message) : void {
            throw new XMLReaderException($message);
        };
    }

    /**
     * Returns the depth of the node in the tree, starting at zero.
     */
    public function depth() : int
    {
        return $this->xmlReader->depth;
    }

    /**
     * Returns the qualified name of the current node.
     *
     * Returns an empty string if the current node does not have a name.
     */
    public function name() : string
    {
        return $this->xmlReader->name;
    }

    /**
     * Returns the type of the current node, as an XMLReader::* constant.
     */
    public function nodeType() : int
    {
        return $this->xmlReader->nodeType;
    }

    /**
     * Opens a URI containing an XML document to parse.
     *
     * @param string      $uri      URI pointing to the document.
     * @param string|null $encoding The document encoding or NULL.
     * @param int         $options  A bitmask of the LIBXML_* constants.
     *
     * @throws XMLReaderException
     */
    public function open(string $uri, ?string $encoding = null, int $options = 0) : void
    {
        set_error_handler($this->errorHandler);

        try {
            $result = $this->xmlReader->open($uri, $encoding, $options);
        } finally {
            restore_error_handler();
        }

        if ($result !== true) {
            throw XMLReaderException::unknownError(__FUNCTION__);
        }
    }

    /**
     * Closes the input the XMLReader object is currently parsing.
     *
     * @throws XMLReaderException
     */
    public function close() : void
    {
        set_error_handler($this->errorHandler);

        try {
            $result = $this->xmlReader->close();
        } finally {
            restore_error_handler();
        }

        if ($result !== true) {
            throw XMLReaderException::unknownError(__FUNCTION__);
        }
    }

    /**
     * Returns a copy of the current node as a DOM object.
     *
     * @throws XMLReaderException
     */
    public function expand(?\DOMNode $baseNode = null) : \DOMNode
    {
        set_error_handler($this->errorHandler);

        try {
            /** @psalm-suppress PossiblyNullArgument https://github.com/vimeo/psalm/pull/8641 */
            $result = $this->xmlReader->expand($baseNode);
        } finally {
            restore_error_handler();
        }

        if ($result === false) {
            throw XMLReaderException::unknownError(__FUNCTION__);
        }

        return $result;
    }

    /**
     * Moves the cursor to the next node in the document.
     *
     * @return bool True if successful, false is there are no more nodes.
     *
     * @throws XMLReaderException
     */
    public function read() : bool
    {
        set_error_handler($this->errorHandler);

        try {
            $result = $this->xmlReader->read();
        } finally {
            restore_error_handler();
        }

        return $result;
    }

    /**
     * Moves the cursor to the next node in the document, skipping all subtrees.
     *
     * @return bool True if successful, false is there are no more nodes.
     *
     * @throws XMLReaderException
     */
    public function next(?string $localName = null) : bool
    {
        set_error_handler($this->errorHandler);

        try {
            if ($localName !== null) {
                $result = $this->xmlReader->next($localName);
            } else {
                $result = $this->xmlReader->next();
            }
        } finally {
            restore_error_handler();
        }

        return $result;
    }
}
