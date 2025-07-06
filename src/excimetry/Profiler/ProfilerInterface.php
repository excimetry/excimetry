<?php

declare(strict_types=1);

namespace Excimetry\Profiler;

/**
 * Interface for profiler implementations.
 * 
 * This interface defines the contract for profilers in the Excimetry library.
 * Implementations should provide methods for configuring, starting, stopping,
 * and retrieving profiling data.
 */
interface ProfilerInterface
{
    /**
     * Set the sampling period in seconds.
     * 
     * @param float $seconds The sampling period in seconds
     * @return self
     */
    public function setPeriod(float $seconds): self;
    
    /**
     * Set the profiling mode.
     * 
     * @param string $mode The profiling mode (e.g., 'wall', 'cpu')
     * @return self
     */
    public function setMode(string $mode): self;
    
    /**
     * Start the profiler.
     * 
     * @return self
     */
    public function start(): self;
    
    /**
     * Stop the profiler.
     * 
     * @return self
     */
    public function stop(): self;
    
    /**
     * Reset the profiler.
     * 
     * @return self
     */
    public function reset(): self;
    
    /**
     * Get the profiling log.
     * 
     * @return mixed The profiling log object
     */
    public function getLog(): mixed;
    
    /**
     * Check if the profiler is currently running.
     * 
     * @return bool True if the profiler is running, false otherwise
     */
    public function isRunning(): bool;
}