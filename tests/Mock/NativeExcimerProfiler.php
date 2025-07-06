<?php

/**
 * Mock implementation of the native ExcimerProfiler class for testing.
 * 
 * This class is used to mock the native \ExcimerProfiler class in tests.
 * It provides the same interface as the native class but with simplified
 * implementations for testing purposes.
 */
class ExcimerProfiler
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
     * Create a new ExcimerProfiler instance.
     */
    public function __construct()
    {
    }

    /**
     * Set the sampling period in seconds.
     * 
     * @param float $seconds The sampling period in seconds
     * @return void
     */
    public function setPeriod(float $seconds): void
    {
        $this->period = $seconds;
    }

    /**
     * Start the profiler.
     * 
     * @return void
     */
    public function start(): void
    {
        $this->running = true;
    }

    /**
     * Stop the profiler.
     * 
     * @return void
     */
    public function stop(): void
    {
        $this->running = false;
    }

    /**
     * Get the profiling log.
     * 
     * @return string The raw log data
     */
    public function getLog(): string
    {
        return $this->rawLog;
    }
}