<?php

declare(strict_types=1);

namespace Excimetry\Tests\Mock;

use Excimetry\Backend\HttpBackend;
use Excimetry\Profiler\ExcimerLog;

/**
 * Mock implementation of the HttpBackend class for testing.
 *
 * This class overrides the methods that use cURL to allow testing without
 * making actual HTTP requests.
 */
class HttpBackendMock extends HttpBackend
{
    /**
     * @var bool Whether doSend should succeed
     */
    private bool $doSendSuccess = true;

    /**
     * @var bool Whether isAvailable should succeed
     */
    private bool $isAvailableSuccess = true;

    /**
     * @var array Method call history
     */
    private array $calls = [];

    /**
     * @var string|null The last exported data
     */
    private ?string $lastExportedData = null;

    /**
     * @var array|null The last headers used
     */
    private ?array $lastHeaders = null;

    /**
     * {@inheritdoc}
     */
    protected function doSend(ExcimerLog $log): bool
    {
        $this->recordCall('doSend', ['log' => $log]);

        // Export the data (this will call the exporter's export method)
        $this->lastExportedData = $this->getExporter()->export($log);

        // Build the headers
        $this->lastHeaders = array_merge([
            'Content-Type: ' . $this->getExporter()->getContentType(),
            'Content-Length: ' . strlen($this->lastExportedData),
        ], $this->getHeaders());

        return $this->doSendSuccess;
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable(): bool
    {
        $this->recordCall('isAvailable');
        return $this->isAvailableSuccess;
    }

    /**
     * {@inheritdoc}
     */
    protected function sendAsync(ExcimerLog $log): void
    {
        $this->recordCall('sendAsync', ['log' => $log]);

        // Export the data (this will call the exporter's export method)
        $this->lastExportedData = $this->getExporter()->export($log);
    }

    /**
     * Set whether doSend should succeed.
     *
     * @param bool $success Whether doSend should succeed
     * @return self
     */
    public function setDoSendSuccess(bool $success): self
    {
        $this->doSendSuccess = $success;
        return $this;
    }

    /**
     * Set whether isAvailable should succeed.
     *
     * @param bool $success Whether isAvailable should succeed
     * @return self
     */
    public function setIsAvailableSuccess(bool $success): self
    {
        $this->isAvailableSuccess = $success;
        return $this;
    }

    /**
     * Get the method call history.
     *
     * @return array The method call history
     */
    public function getCalls(): array
    {
        return $this->calls;
    }

    /**
     * Check if a method was called.
     *
     * @param string $method The method name
     * @return bool True if the method was called, false otherwise
     */
    public function wasCalled(string $method): bool
    {
        foreach ($this->calls as $call) {
            if ($call['method'] === $method) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the number of times a method was called.
     *
     * @param string $method The method name
     * @return int The number of times the method was called
     */
    public function getCallCount(string $method): int
    {
        $count = 0;

        foreach ($this->calls as $call) {
            if ($call['method'] === $method) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get the last exported data.
     *
     * @return string|null The last exported data
     */
    public function getLastExportedData(): ?string
    {
        return $this->lastExportedData;
    }

    /**
     * Get the last headers used.
     *
     * @return array|null The last headers used
     */
    public function getLastHeaders(): ?array
    {
        return $this->lastHeaders;
    }

    /**
     * Record a method call.
     *
     * @param string $method The method name
     * @param array $args The method arguments
     * @return void
     */
    private function recordCall(string $method, array $args = []): void
    {
        // Create a safe copy of args to avoid storing large objects like ExcimerLog
        $safeArgs = [];
        foreach ($args as $key => $value) {
            if ($value instanceof ExcimerLog) {
                // Store only a reference to the log, not the entire object
                $safeArgs[$key] = '[ExcimerLog instance]';
            } else {
                $safeArgs[$key] = $value;
            }
        }

        $this->calls[] = [
            'method' => $method,
            'args' => $safeArgs,
            'time' => microtime(true),
        ];
    }
}
