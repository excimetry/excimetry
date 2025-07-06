<?php

declare(strict_types=1);

namespace Excimetry\Backend;

use Excimetry\Profiler\ExcimerLog;
use Excimetry\Exporter\OTLPExporter;
use Excimetry\Exporter\ExporterInterface;

/**
 * Backend for sending profile data to an OpenTelemetry Collector.
 */
final class OTLPBackend extends HttpBackend
{
    /**
     * @var string The service name to include in the OTLP data
     */
    private string $serviceName;
    
    /**
     * Create a new OTLPBackend instance.
     * 
     * @param string $collectorUrl The URL of the OpenTelemetry Collector
     * @param string $serviceName The service name to include in the OTLP data
     * @param ExporterInterface|null $exporter The exporter to use, or null to use OTLPExporter
     */
    public function __construct(
        string $collectorUrl,
        string $serviceName = 'php-application',
        ?ExporterInterface $exporter = null
    ) {
        // Use OTLPExporter by default
        if ($exporter === null) {
            $exporter = new OTLPExporter($serviceName);
        }
        
        // Build the collector URL
        $url = rtrim($collectorUrl, '/') . '/v1/traces';
        
        // Set up headers for OTLP
        $headers = [
            'Accept: application/json',
        ];
        
        parent::__construct($exporter, $url, $headers);
        
        $this->serviceName = $serviceName;
    }
    
    /**
     * Set the service name to include in the OTLP data.
     * 
     * @param string $serviceName The service name
     * @return self
     */
    public function setServiceName(string $serviceName): self
    {
        $this->serviceName = $serviceName;
        
        // Update the exporter if it's an OTLPExporter
        $exporter = $this->getExporter();
        if ($exporter instanceof OTLPExporter) {
            $exporter->setServiceName($serviceName);
        }
        
        return $this;
    }
    
    /**
     * Get the service name to include in the OTLP data.
     * 
     * @return string The service name
     */
    public function getServiceName(): string
    {
        return $this->serviceName;
    }
    
    /**
     * Set the format to use for the OTLP data.
     * 
     * @param string $format The format (json or protobuf)
     * @return self
     */
    public function setFormat(string $format): self
    {
        // Update the exporter if it's an OTLPExporter
        $exporter = $this->getExporter();
        if ($exporter instanceof OTLPExporter) {
            $exporter->setFormat($format);
            
            // Update the headers based on the format
            $headers = $this->getHeaders();
            $contentTypeHeader = 'Accept: ' . $exporter->getContentType();
            
            // Replace the Accept header
            $headers = array_filter($headers, function($header) {
                return strpos($header, 'Accept:') !== 0;
            });
            
            $headers[] = $contentTypeHeader;
            $this->setHeaders($headers);
        }
        
        return $this;
    }
    
    /**
     * Get the format used for the OTLP data.
     * 
     * @return string The format
     */
    public function getFormat(): string
    {
        $exporter = $this->getExporter();
        if ($exporter instanceof OTLPExporter) {
            return $exporter->getFormat();
        }
        
        return 'json';
    }
    
    /**
     * Add a trace ID to the profile.
     * 
     * @param string $traceId The trace ID
     * @return self
     */
    public function addTraceId(string $traceId): self
    {
        // Add the trace ID to the exporter's metadata
        $exporter = $this->getExporter();
        if ($exporter instanceof OTLPExporter) {
            $exporter->addMetadata('trace_id', $traceId);
        }
        
        return $this;
    }
    
    /**
     * Add a span ID to the profile.
     * 
     * @param string $spanId The span ID
     * @return self
     */
    public function addSpanId(string $spanId): self
    {
        // Add the span ID to the exporter's metadata
        $exporter = $this->getExporter();
        if ($exporter instanceof OTLPExporter) {
            $exporter->addMetadata('span_id', $spanId);
        }
        
        return $this;
    }
}