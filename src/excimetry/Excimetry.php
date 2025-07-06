<?php

declare(strict_types=1);

namespace Excimetry;

use Excimetry\Profiler\ExcimerProfiler;
use Excimetry\Profiler\ExcimerLog;
use Excimetry\Exporter\ExporterInterface;
use Excimetry\Exporter\SpeedscopeExporter;
use Excimetry\Exporter\CollapsedExporter;
use Excimetry\Exporter\OTLPExporter;
use Excimetry\Backend\FileBackend;
use Excimetry\Backend\BackendInterface;

/**
 * Main class for the Excimetry library.
 * 
 * This class provides a simplified interface to the Excimetry library.
 */
final class Excimetry
{
    /**
     * @var ExcimerProfiler The profiler instance
     */
    private ExcimerProfiler $profiler;

    /**
     * @var ExcimerLog|null The profiling log
     */
    private ?ExcimerLog $log = null;

    /**
     * @var ExcimetryConfig The configuration
     */
    private ExcimetryConfig $config;

    /**
     * Create a new Excimetry instance.
     * 
     * @param ExcimetryConfig|array $config Configuration for the library
     */
    public function __construct(ExcimetryConfig|array $config = [])
    {
        // Create a configuration object if an array was provided
        if (is_array($config)) {
            $this->config = new ExcimetryConfig($config);
        } else {
            $this->config = $config;
        }

        // Create the profiler with the configuration
        $this->profiler = new ExcimerProfiler([
            'period' => $this->config->getPeriod(),
            'mode' => $this->config->getMode(),
            'metadata' => $this->config->getMetadata(),
        ]);
    }

    /**
     * Start profiling.
     * 
     * @return self
     */
    public function start(): self
    {
        $this->profiler->start();
        return $this;
    }

    /**
     * Stop profiling.
     * 
     * @return self
     */
    public function stop(): self
    {
        $this->profiler->stop();
        $this->log = $this->profiler->getLog();
        return $this;
    }

    /**
     * Reset the profiler.
     * 
     * @return self
     */
    public function reset(): self
    {
        $this->profiler->reset();
        $this->log = null;
        return $this;
    }

    /**
     * Get the profiling log.
     * 
     * @return ExcimerLog The profiling log
     * @throws \RuntimeException If profiling has not been stopped
     */
    public function getLog(): ExcimerLog
    {
        if ($this->log === null) {
            throw new \RuntimeException('Profiling has not been stopped. Call stop() first.');
        }

        return $this->log;
    }

    /**
     * Export the profiling data using the specified exporter.
     * 
     * @param ExporterInterface|null $exporter The exporter to use, or null to use the default exporter
     * @return mixed The exported data
     * @throws \RuntimeException If profiling has not been stopped
     */
    public function export(?ExporterInterface $exporter = null): mixed
    {
        if ($exporter === null) {
            $exporter = $this->createExporter();
        }

        return $exporter->export($this->getLog());
    }

    /**
     * Save the profiling data to a file.
     * 
     * @param string|null $filename The filename to use, or null to generate one
     * @param ExporterInterface|null $exporter The exporter to use, or null to use the default exporter
     * @return bool True if the data was saved successfully, false otherwise
     * @throws \RuntimeException If profiling has not been stopped
     */
    public function save(?string $filename = null, ?ExporterInterface $exporter = null): bool
    {
        if ($exporter === null) {
            $exporter = $this->createExporter();
        }

        $backend = new FileBackend(
            $exporter,
            $this->config->getOutputDirectory(),
            $filename
        );

        return $backend->send($this->getLog());
    }

    /**
     * Send the profiling data to a backend.
     * 
     * @param BackendInterface $backend The backend to send the data to
     * @return bool True if the data was sent successfully, false otherwise
     * @throws \RuntimeException If profiling has not been stopped
     */
    public function send(BackendInterface $backend): bool
    {
        return $backend->send($this->getLog());
    }

    /**
     * Create an exporter based on the configuration.
     * 
     * @return ExporterInterface The exporter
     */
    public function createExporter(): ExporterInterface
    {
        return match ($this->config->getExportFormat()) {
            'collapsed' => new CollapsedExporter(),
            'speedscope' => new SpeedscopeExporter(),
            'otlp' => new OTLPExporter(),
            default => new SpeedscopeExporter(),
        };
    }

    /**
     * Set the sampling period in seconds.
     * 
     * @param float $seconds The sampling period in seconds
     * @return self
     */
    public function setPeriod(float $seconds): self
    {
        $this->profiler->setPeriod($seconds);
        return $this;
    }

    /**
     * Set the profiling mode.
     * 
     * @param string $mode The profiling mode (e.g., 'wall', 'cpu')
     * @return self
     */
    public function setMode(string $mode): self
    {
        $this->profiler->setMode($mode);
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
        $this->profiler->addMetadata($key, $value);
        return $this;
    }

    /**
     * Set multiple metadata values at once.
     * 
     * @param array $metadata The metadata to set
     * @return self
     */
    public function setMetadata(array $metadata): self
    {
        $this->profiler->setMetadata($metadata);
        return $this;
    }

    /**
     * Get the profiler instance.
     * 
     * @return ExcimerProfiler The profiler instance
     */
    public function getProfiler(): ExcimerProfiler
    {
        return $this->profiler;
    }

    /**
     * Get the configuration.
     * 
     * @return ExcimetryConfig The configuration
     */
    public function getConfig(): ExcimetryConfig
    {
        return $this->config;
    }

    /**
     * Check if the profiler is currently running.
     * 
     * @return bool True if the profiler is running, false otherwise
     */
    public function isRunning(): bool
    {
        return $this->profiler->isRunning();
    }

    /**
     * Create a new Excimetry instance with default configuration.
     * 
     * @return self
     */
    public static function create(): self
    {
        return new self(ExcimetryConfig::createDefault());
    }
}
