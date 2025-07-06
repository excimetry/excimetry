<?php

declare(strict_types=1);

namespace Excimetry\Backend;

use Excimetry\Profiler\ExcimerLog;
use Excimetry\Exporter\ExporterInterface;

/**
 * Abstract base class for profile backends.
 * 
 * This class implements common functionality for all backends.
 */
abstract class AbstractBackend implements BackendInterface
{
    /**
     * @var ExporterInterface The exporter to use for converting the profile data
     */
    protected ExporterInterface $exporter;
    
    /**
     * @var int The maximum number of retries
     */
    protected int $maxRetries = 3;
    
    /**
     * @var int The delay between retries in milliseconds
     */
    protected int $retryDelay = 1000;
    
    /**
     * @var bool Whether to send the profile data asynchronously
     */
    protected bool $async = false;
    
    /**
     * Create a new backend instance.
     * 
     * @param ExporterInterface $exporter The exporter to use
     */
    public function __construct(ExporterInterface $exporter)
    {
        $this->exporter = $exporter;
    }
    
    /**
     * {@inheritdoc}
     */
    public function setExporter(ExporterInterface $exporter): self
    {
        $this->exporter = $exporter;
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getExporter(): ExporterInterface
    {
        return $this->exporter;
    }
    
    /**
     * {@inheritdoc}
     */
    public function setRetryConfig(int $maxRetries, int $retryDelay): self
    {
        $this->maxRetries = $maxRetries;
        $this->retryDelay = $retryDelay;
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function setAsync(bool $async): self
    {
        $this->async = $async;
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function isAvailable(): bool
    {
        return true;
    }
    
    /**
     * Send the profile data with retries.
     * 
     * @param ExcimerLog $log The profile data to send
     * @return bool True if the data was sent successfully, false otherwise
     */
    protected function sendWithRetries(ExcimerLog $log): bool
    {
        $retries = 0;
        $success = false;
        
        while ($retries <= $this->maxRetries && !$success) {
            try {
                $success = $this->doSend($log);
            } catch (\Exception $e) {
                // Log the exception
                error_log("Error sending profile data: " . $e->getMessage());
                
                // Retry if we haven't reached the maximum number of retries
                if ($retries < $this->maxRetries) {
                    $retries++;
                    usleep($this->retryDelay * 1000); // Convert milliseconds to microseconds
                    continue;
                }
                
                return false;
            }
            
            if (!$success && $retries < $this->maxRetries) {
                $retries++;
                usleep($this->retryDelay * 1000); // Convert milliseconds to microseconds
            }
        }
        
        return $success;
    }
    
    /**
     * Send the profile data to the backend.
     * 
     * This method should be implemented by concrete backends.
     * 
     * @param ExcimerLog $log The profile data to send
     * @return bool True if the data was sent successfully, false otherwise
     */
    abstract protected function doSend(ExcimerLog $log): bool;
}