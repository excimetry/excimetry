<?php

declare(strict_types=1);

namespace Excimetry\Tests\Mock;

use Excimetry\Profiler\ExcimerLog;

/**
 * Mock implementation of the native ExcimerProfiler class for testing.
 * 
 * This class mimics the behavior of the native ExcimerProfiler but allows
 * controlling its behavior for testing purposes.
 */
class ExcimerProfilerMock
{
    /**
     * @var float The sampling period in seconds
     */
    private float $period = 0.01;

    /**
     * @var bool Whether the profiler is running
     */
    private bool $running = false;

    /**
     * @var string The raw log data to return
     */
    private string $rawLog = "main;App\\Controller\\HomeController;index 1";

    /**
     * @var array Additional metadata for the profile
     */
    private array $metadata = [];

    /**
     * @var string The profiling mode
     */
    private string $mode = 'wall';

    /**
     * @var array Method call history
     */
    private array $calls = [];

    /**
     * Create a new ExcimerProfilerMock instance.
     */
    public function __construct()
    {
        // Record the constructor call
        $this->recordCall('__construct');
    }

    /**
     * Set the sampling period in seconds.
     * 
     * @param float $seconds The sampling period in seconds
     * @return void
     */
    public function setPeriod(float $seconds): void
    {
        $this->recordCall('setPeriod', ['seconds' => $seconds]);
        $this->period = $seconds;
    }

    /**
     * Start the profiler.
     * 
     * @return void
     */
    public function start(): void
    {
        $this->recordCall('start');
        $this->running = true;
    }

    /**
     * Stop the profiler.
     * 
     * @return void
     */
    public function stop(): void
    {
        $this->recordCall('stop');
        $this->running = false;
    }

    /**
     * Reset the profiler.
     * 
     * @return self
     */
    public function reset(): self
    {
        $this->recordCall('reset');
        $this->stop();
        $this->metadata = [];
        return $this;
    }

    /**
     * Get the profiling log.
     * 
     * @return ExcimerLog The profiling log
     */
    public function getLog(): ExcimerLog
    {
        $this->recordCall('getLog');

        // Add standard metadata
        $metadata = array_merge([
            'timestamp' => time(),
            'period' => $this->period,
            'mode' => $this->mode,
            'php_version' => PHP_VERSION,
            'os' => PHP_OS,
        ], $this->metadata);

        return new ExcimerLog($this->rawLog, $metadata);
    }

    /**
     * Set the raw log data to return.
     * 
     * @param string $rawLog The raw log data
     * @return self
     */
    public function setRawLog(string $rawLog): self
    {
        $this->rawLog = $rawLog;
        return $this;
    }

    /**
     * Set the profiling mode.
     * 
     * @param string $mode The profiling mode
     * @return self
     */
    public function setMode(string $mode): self
    {
        $this->recordCall('setMode', ['mode' => $mode]);
        $this->mode = $mode;
        return $this;
    }

    /**
     * Add metadata to the profile.
     * 
     * @param string $key The metadata key
     * @param mixed $value The metadata value
     * @return self
     */
    public function addMetadata(string $key, mixed $value): self
    {
        $this->recordCall('addMetadata', ['key' => $key, 'value' => $value]);
        $this->metadata[$key] = $value;
        return $this;
    }

    /**
     * Set the metadata for the profile.
     * 
     * @param array $metadata The metadata
     * @return self
     */
    public function setMetadata(array $metadata): self
    {
        $this->recordCall('setMetadata', ['metadata' => $metadata]);
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * Get the metadata for the profile.
     * 
     * @return array The metadata
     */
    public function getMetadata(): array
    {
        $this->recordCall('getMetadata');
        return $this->metadata;
    }

    /**
     * Check if the profiler is running.
     * 
     * @return bool True if the profiler is running, false otherwise
     */
    public function isRunning(): bool
    {
        $this->recordCall('isRunning');
        return $this->running;
    }

    /**
     * Get the sampling period.
     * 
     * @return float The sampling period in seconds
     */
    public function getPeriod(): float
    {
        $this->recordCall('getPeriod');
        return $this->period;
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
     * Record a method call.
     * 
     * @param string $method The method name
     * @param array $args The method arguments
     * @return void
     */
    private function recordCall(string $method, array $args = []): void
    {
        $this->calls[] = [
            'method' => $method,
            'args' => $args,
            'time' => microtime(true),
        ];
    }
}
