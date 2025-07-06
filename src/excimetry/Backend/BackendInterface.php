<?php

declare(strict_types=1);

namespace Excimetry\Backend;

use Excimetry\Profiler\ExcimerLog;
use Excimetry\Exporter\ExporterInterface;

/**
 * Interface for profile backends.
 * 
 * This interface defines the contract for backends that send profile data
 * to various destinations.
 */
interface BackendInterface
{
    /**
     * Send the profile data to the backend.
     * 
     * @param ExcimerLog $log The profile data to send
     * @return bool True if the data was sent successfully, false otherwise
     */
    public function send(ExcimerLog $log): bool;
    
    /**
     * Set the exporter to use for converting the profile data.
     * 
     * @param ExporterInterface $exporter The exporter to use
     * @return self
     */
    public function setExporter(ExporterInterface $exporter): self;
    
    /**
     * Get the exporter used for converting the profile data.
     * 
     * @return ExporterInterface The exporter
     */
    public function getExporter(): ExporterInterface;
    
    /**
     * Set the retry configuration.
     * 
     * @param int $maxRetries The maximum number of retries
     * @param int $retryDelay The delay between retries in milliseconds
     * @return self
     */
    public function setRetryConfig(int $maxRetries, int $retryDelay): self;
    
    /**
     * Set whether to send the profile data asynchronously.
     * 
     * @param bool $async True to send asynchronously, false to send synchronously
     * @return self
     */
    public function setAsync(bool $async): self;
    
    /**
     * Check if the backend is available.
     * 
     * @return bool True if the backend is available, false otherwise
     */
    public function isAvailable(): bool;
}