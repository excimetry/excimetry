<?php

declare(strict_types=1);

namespace Excimetry;

/**
 * Configuration class for the Excimetry library.
 * 
 * This class provides a way to configure the library with sensible defaults.
 */
final class ExcimetryConfig
{
    /**
     * @var float The sampling period in seconds
     */
    private float $period = 0.01; // Default: 10ms

    /**
     * @var ExcimetryModeEnum The profiling mode
     */
    private ExcimetryModeEnum $mode = ExcimetryModeEnum::wall; // Default: wall time

    /**
     * @var array Metadata to include with the profile
     */
    private array $metadata = [];

    /**
     * @var string The default export format
     */
    private string $exportFormat = 'speedscope'; // Default: speedscope

    /**
     * @var string The default output directory for file exports
     */
    private string $outputDirectory = 'profiles'; // Default: profiles

    /**
     * @var bool Whether to use async export by default
     */
    private bool $asyncExport = false; // Default: synchronous

    /**
     * @var int The maximum number of retries for exports
     */
    private int $maxRetries = 3; // Default: 3 retries

    /**
     * @var int The delay between retries in milliseconds
     */
    private int $retryDelay = 1000; // Default: 1 second

    /**
     * Create a new ExcimetryConfig instance.
     * 
     * @param array $options Configuration options
     */
    public function __construct(array $options = [])
    {
        // Apply configuration options
        foreach ($options as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($this, $setter)) {
                $this->$setter($value);
            }
        }
    }

    /**
     * Get the sampling period in seconds.
     * 
     * @return float The sampling period
     */
    public function getPeriod(): float
    {
        return $this->period;
    }

    /**
     * Set the sampling period in seconds.
     * 
     * @param float $period The sampling period
     * @return self
     */
    public function setPeriod(float $period): self
    {
        $this->period = $period;
        return $this;
    }

    /**
     * Get the profiling mode.
     * 
     * @return string The profiling mode
     */
    public function getMode(): string
    {
        return $this->mode->value;
    }

    /**
     * Set the profiling mode.
     * 
     * @param string $mode The profiling mode (e.g., 'wall', 'cpu')
     * @return self
     * @throws \InvalidArgumentException If the mode is invalid
     */
    public function setMode(string $mode): self
    {
        if (!in_array($mode, ['wall', 'cpu'])) {
            throw new \InvalidArgumentException(
                "Invalid profiling mode: {$mode}. Supported modes are 'wall' and 'cpu'."
            );
        }

        $this->mode = ExcimetryModeEnum::tryFrom($mode) ?? ExcimetryModeEnum::wall;
        return $this;
    }

    /**
     * Get the metadata to include with the profile.
     * 
     * @return array The metadata
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Set the metadata to include with the profile.
     * 
     * @param array $metadata The metadata
     * @return self
     */
    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * Add metadata to include with the profile.
     * 
     * @param string $key The metadata key
     * @param mixed $value The metadata value
     * @return self
     */
    public function addMetadata(string $key, mixed $value): self
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    /**
     * Get the default export format.
     * 
     * @return string The export format
     */
    public function getExportFormat(): string
    {
        return $this->exportFormat;
    }

    /**
     * Set the default export format.
     * 
     * @param string $format The export format (e.g., 'speedscope', 'collapsed', 'otlp')
     * @return self
     * @throws \InvalidArgumentException If the format is invalid
     */
    public function setExportFormat(string $format): self
    {
        if (!in_array($format, ['speedscope', 'collapsed', 'otlp'])) {
            throw new \InvalidArgumentException(
                "Invalid export format: {$format}. Supported formats are 'speedscope', 'collapsed', and 'otlp'."
            );
        }

        $this->exportFormat = $format;
        return $this;
    }

    /**
     * Get the default output directory for file exports.
     * 
     * @return string The output directory
     */
    public function getOutputDirectory(): string
    {
        return $this->outputDirectory;
    }

    /**
     * Set the default output directory for file exports.
     * 
     * @param string $directory The output directory
     * @return self
     */
    public function setOutputDirectory(string $directory): self
    {
        $this->outputDirectory = rtrim($directory, '/');
        return $this;
    }

    /**
     * Get whether to use async export by default.
     * 
     * @return bool True if async export is enabled, false otherwise
     */
    public function isAsyncExport(): bool
    {
        return $this->asyncExport;
    }

    /**
     * Set whether to use async export by default.
     * 
     * @param bool $async True to enable async export, false to disable
     * @return self
     */
    public function setAsyncExport(bool $async): self
    {
        $this->asyncExport = $async;
        return $this;
    }

    /**
     * Get the maximum number of retries for exports.
     * 
     * @return int The maximum number of retries
     */
    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }

    /**
     * Set the maximum number of retries for exports.
     * 
     * @param int $retries The maximum number of retries
     * @return self
     * @throws \InvalidArgumentException If the number of retries is negative
     */
    public function setMaxRetries(int $retries): self
    {
        if ($retries < 0) {
            throw new \InvalidArgumentException(
                "Invalid number of retries: {$retries}. The number of retries must be non-negative."
            );
        }

        $this->maxRetries = $retries;
        return $this;
    }

    /**
     * Get the delay between retries in milliseconds.
     * 
     * @return int The retry delay
     */
    public function getRetryDelay(): int
    {
        return $this->retryDelay;
    }

    /**
     * Set the delay between retries in milliseconds.
     * 
     * @param int $delay The retry delay
     * @return self
     * @throws \InvalidArgumentException If the delay is negative
     */
    public function setRetryDelay(int $delay): self
    {
        if ($delay < 0) {
            throw new \InvalidArgumentException(
                "Invalid retry delay: {$delay}. The retry delay must be non-negative."
            );
        }

        $this->retryDelay = $delay;
        return $this;
    }

    /**
     * Get the configuration as an array.
     * 
     * @return array The configuration
     */
    public function toArray(): array
    {
        return [
            'period' => $this->period,
            'mode' => $this->mode->value,
            'metadata' => $this->metadata,
            'exportFormat' => $this->exportFormat,
            'outputDirectory' => $this->outputDirectory,
            'asyncExport' => $this->asyncExport,
            'maxRetries' => $this->maxRetries,
            'retryDelay' => $this->retryDelay,
        ];
    }

    /**
     * Create a new ExcimetryConfig instance with default values.
     * 
     * @return self
     */
    public static function createDefault(): self
    {
        return new self();
    }
}
