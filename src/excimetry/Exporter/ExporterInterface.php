<?php

declare(strict_types=1);

namespace Excimetry\Exporter;

use Excimetry\Profiler\ExcimerLog;

/**
 * Interface for profile exporters.
 * 
 * This interface defines the contract for exporters that convert ExcimerLog
 * data to various formats.
 */
interface ExporterInterface
{
    /**
     * Export the profile data to the target format.
     * 
     * @param ExcimerLog $log The profile data to export
     * @return mixed The exported data in the target format
     */
    public function export(ExcimerLog $log): mixed;
    
    /**
     * Get the content type of the exported data.
     * TODO: move content type definition from exporters to backend
     * @return string The content type (e.g., 'text/plain', 'application/json')
     */
    public function getContentType(): string;
    
    /**
     * Get the file extension for the exported data.
     * 
     * @return string The file extension (e.g., 'txt', 'json')
     */
    public function getFileExtension(): string;
}