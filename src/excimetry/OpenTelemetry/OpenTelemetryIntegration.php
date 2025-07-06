<?php

declare(strict_types=1);

namespace Excimetry\OpenTelemetry;

use Excimetry\Profiler\ExcimerProfiler;
use Excimetry\Backend\OTLPBackend;
use Excimetry\Exporter\OTLPExporter;

/**
 * Integration with the OpenTelemetry SDK for PHP.
 * 
 * This class provides methods for connecting Excimetry with OpenTelemetry.
 */
final class OpenTelemetryIntegration
{
    /**
     * @var ExcimerProfiler The profiler instance
     */
    private ExcimerProfiler $profiler;

    /**
     * @var OTLPBackend The backend for sending profiles to OpenTelemetry
     */
    private OTLPBackend $backend;

    /**
     * Create a new OpenTelemetryIntegration instance.
     * 
     * @param ExcimerProfiler $profiler The profiler instance
     * @param OTLPBackend $backend The backend for sending profiles to OpenTelemetry
     */
    public function __construct(ExcimerProfiler $profiler, OTLPBackend $backend)
    {
        $this->profiler = $profiler;
        $this->backend = $backend;
    }

    /**
     * Create a new OpenTelemetryIntegration instance with default configuration.
     * 
     * @param string $collectorUrl The URL of the OpenTelemetry Collector
     * @param string $serviceName The service name to include in the OTLP data
     * @param array $options Configuration options for the profiler
     * @return OpenTelemetryIntegration
     */
    public static function create(
        string $collectorUrl,
        string $serviceName = 'php-application',
        array $options = []
    ): OpenTelemetryIntegration {
        $profiler = new ExcimerProfiler($options);
        $exporter = new OTLPExporter($serviceName);
        $backend = new OTLPBackend($collectorUrl, $serviceName, $exporter);

        return new self($profiler, $backend);
    }

    /**
     * Start profiling.
     * 
     * @return OpenTelemetryIntegration
     */
    public function start(): OpenTelemetryIntegration
    {
        $this->profiler->start();
        return $this;
    }

    /**
     * Stop profiling and send the profile to OpenTelemetry.
     * 
     * @return OpenTelemetryIntegration
     */
    public function stop(): OpenTelemetryIntegration
    {
        $this->profiler->stop();
        $log = $this->profiler->getLog();
        $this->backend->send($log);

        return $this;
    }

    /**
     * Reset the profiler.
     * 
     * @return OpenTelemetryIntegration
     */
    public function reset(): OpenTelemetryIntegration
    {
        $this->profiler->reset();
        return $this;
    }

    /**
     * Add a trace ID to the profile.
     * 
     * @param string $traceId The trace ID
     * @return OpenTelemetryIntegration
     */
    public function addTraceId(string $traceId): OpenTelemetryIntegration
    {
        $this->backend->addTraceId($traceId);
        $this->profiler->addMetadata('trace_id', $traceId);

        return $this;
    }

    /**
     * Add a span ID to the profile.
     * 
     * @param string $spanId The span ID
     * @return OpenTelemetryIntegration
     */
    public function addSpanId(string $spanId): OpenTelemetryIntegration
    {
        $this->backend->addSpanId($spanId);
        $this->profiler->addMetadata('span_id', $spanId);

        return $this;
    }

    /**
     * Add metadata to the profile.
     * 
     * @param string $key The metadata key
     * @param mixed $value The metadata value
     * @return OpenTelemetryIntegration
     */
    public function addMetadata(string $key, mixed $value): OpenTelemetryIntegration
    {
        $this->profiler->addMetadata($key, $value);
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
     * Get the backend instance.
     * 
     * @return OTLPBackend The backend instance
     */
    public function getBackend(): OTLPBackend
    {
        return $this->backend;
    }
}
