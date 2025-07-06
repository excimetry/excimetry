<?php

declare(strict_types=1);

namespace Excimetry\Exporter;

use Excimetry\Profiler\ExcimerLog;

/**
 * Exporter for the Speedscope JSON format.
 * 
 * This exporter converts ExcimerLog data to the JSON format used by
 * speedscope.app for interactive flame graphs.
 */
final class SpeedscopeExporter implements ExporterInterface
{
    /**
     * @var string The name to use for the profile
     */
    private string $profileName;
    
    /**
     * Create a new SpeedscopeExporter instance.
     * 
     * @param string $profileName The name to use for the profile
     */
    public function __construct(string $profileName = 'Excimer Profile')
    {
        $this->profileName = $profileName;
    }
    
    /**
     * {@inheritdoc}
     */
    public function export(ExcimerLog $log): string
    {
        $data = $log->getSpeedscopeData();
        
        // Override the profile name if specified
        if (!empty($this->profileName) && isset($data['profiles'][0])) {
            $data['profiles'][0]['name'] = $this->profileName;
        }
        
        return json_encode($data, JSON_PRETTY_PRINT);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getContentType(): string
    {
        return 'application/json';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getFileExtension(): string
    {
        return 'json';
    }
    
    /**
     * Set the profile name.
     * 
     * @param string $profileName The name to use for the profile
     * @return self
     */
    public function setProfileName(string $profileName): self
    {
        $this->profileName = $profileName;
        return $this;
    }
    
    /**
     * Get the profile name.
     * 
     * @return string The profile name
     */
    public function getProfileName(): string
    {
        return $this->profileName;
    }
}