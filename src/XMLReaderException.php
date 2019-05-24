<?php

declare(strict_types=1);

namespace BenMorel\XMLStreamer;

/**
 * Exception thrown when an error occurs while reading an XML file.
 */
class XMLReaderException extends XMLStreamerException
{
    /**
     * Creates an exception representing an unknown error.
     *
     * This method should never be called, as XMLReader triggers a PHP warning every time something goes wrong, and
     * this warning is caught and converted to an exception. If for any reason though, an XMLReader method unexpectedly
     * returned false and no PHP warning was triggered with an explanation, this method would be called.
     *
     * @param string $method
     *
     * @return XMLReaderException
     */
    public static function unknownError(string $method) : self
    {
        return new self(sprintf('Unknown error in XMLReader::%s()', $method));
    }
}
