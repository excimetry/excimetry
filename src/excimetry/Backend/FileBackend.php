<?php

declare(strict_types=1);

namespace Excimetry\Backend;

use Excimetry\Profiler\ExcimerLog;
use Excimetry\Exporter\ExporterInterface;

/**
 * Backend for saving profile data to a local file.
 */
final class FileBackend extends AbstractBackend
{
    /**
     * @var string The directory to save files in
     */
    private string $directory;
    
    /**
     * @var string|null The filename to use, or null to generate one
     */
    private ?string $filename;
    
    /**
     * Create a new FileBackend instance.
     * 
     * @param ExporterInterface $exporter The exporter to use
     * @param string $directory The directory to save files in
     * @param string|null $filename The filename to use, or null to generate one
     */
    public function __construct(
        ExporterInterface $exporter,
        string $directory = 'profiles',
        ?string $filename = null
    ) {
        parent::__construct($exporter);
        
        $this->directory = rtrim($directory, '/');
        $this->filename = $filename;
    }
    
    /**
     * {@inheritdoc}
     */
    public function send(ExcimerLog $log): bool
    {
        return $this->sendWithRetries($log);
    }
    
    /**
     * {@inheritdoc}
     */
    protected function doSend(ExcimerLog $log): bool
    {
        // Create the directory if it doesn't exist
        if (!is_dir($this->directory) && !mkdir($this->directory, 0755, true)) {
            error_log("Failed to create directory: {$this->directory}");
            return false;
        }
        
        // Generate a filename if one wasn't provided
        $filename = $this->filename;
        if ($filename === null) {
            $timestamp = date('YmdHis');
            $extension = $this->exporter->getFileExtension();
            $filename = "profile_{$timestamp}.{$extension}";
        }
        
        // Build the full path
        $path = "{$this->directory}/{$filename}";
        
        // Export the data
        $data = $this->exporter->export($log);
        
        // Write the data to the file
        $result = file_put_contents($path, $data);
        
        return $result !== false;
    }
    
    /**
     * Set the directory to save files in.
     * 
     * @param string $directory The directory to save files in
     * @return self
     */
    public function setDirectory(string $directory): self
    {
        $this->directory = rtrim($directory, '/');
        return $this;
    }
    
    /**
     * Get the directory to save files in.
     * 
     * @return string The directory
     */
    public function getDirectory(): string
    {
        return $this->directory;
    }
    
    /**
     * Set the filename to use.
     * 
     * @param string|null $filename The filename to use, or null to generate one
     * @return self
     */
    public function setFilename(?string $filename): self
    {
        $this->filename = $filename;
        return $this;
    }
    
    /**
     * Get the filename to use.
     * 
     * @return string|null The filename
     */
    public function getFilename(): ?string
    {
        return $this->filename;
    }
}