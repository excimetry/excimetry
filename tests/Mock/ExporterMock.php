<?php

declare(strict_types=1);

namespace Excimetry\Tests\Mock;

use Excimetry\Exporter\ExporterInterface;
use Excimetry\Profiler\ExcimerLog;

/**
 * Mock implementation of the ExporterInterface for testing.
 * 
 * This class mimics the behavior of an exporter but allows controlling
 * its behavior for testing purposes.
 */
class ExporterMock implements ExporterInterface
{
    /**
     * @var string The data to return from export
     */
    private string $exportData = 'mock export data';
    
    /**
     * @var string The content type to return
     */
    private string $contentType = 'text/plain';
    
    /**
     * @var string The file extension to return
     */
    private string $fileExtension = 'txt';
    
    /**
     * @var array Method call history
     */
    private array $calls = [];
    
    /**
     * {@inheritdoc}
     */
    public function export(ExcimerLog $log): string
    {
        $this->recordCall('export', ['log' => $log]);
        return $this->exportData;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getContentType(): string
    {
        $this->recordCall('getContentType');
        return $this->contentType;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getFileExtension(): string
    {
        $this->recordCall('getFileExtension');
        return $this->fileExtension;
    }
    
    /**
     * Set the data to return from export.
     * 
     * @param string $data The data to return
     * @return self
     */
    public function setExportData(string $data): self
    {
        $this->exportData = $data;
        return $this;
    }
    
    /**
     * Set the content type to return.
     * 
     * @param string $contentType The content type
     * @return self
     */
    public function setContentType(string $contentType): self
    {
        $this->contentType = $contentType;
        return $this;
    }
    
    /**
     * Set the file extension to return.
     * 
     * @param string $fileExtension The file extension
     * @return self
     */
    public function setFileExtension(string $fileExtension): self
    {
        $this->fileExtension = $fileExtension;
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