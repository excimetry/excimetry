<?php

/**
 * Mock implementation of the native ExcimerLog class for testing.
 * 
 * This class is used to mock the native \ExcimerLog class in tests.
 * It provides the same interface as the native class but with simplified
 * implementations for testing purposes.
 */
class ExcimerLog
{
    /**
     * @var string The raw log data
     */
    private string $rawLog;

    /**
     * Create a new ExcimerLog instance.
     * 
     * @param string $rawLog The raw log data
     */
    public function __construct(string $rawLog)
    {
        $this->rawLog = $rawLog;
    }

    /**
     * Convert the log to a string.
     * 
     * @return string The raw log data
     */
    public function __toString(): string
    {
        return $this->rawLog;
    }
}