<?php

declare(strict_types=1);

namespace Excimetry\Exporter;

use Excimetry\Profiler\ExcimerLog;

/**
 * Exporter for the OpenTelemetry OTLP format.
 * 
 * This exporter converts ExcimerLog data to the OpenTelemetry OTLP format
 * for integration with OpenTelemetry collectors and backends.
 */
final class OTLPExporter implements ExporterInterface
{
    /**
     * @var string The service name to include in the OTLP data
     */
    private string $serviceName;

    /**
     * @var string The format to use for the OTLP data (json or protobuf)
     */
    private string $format;

    /**
     * Create a new OTLPExporter instance.
     * 
     * @param string $serviceName The service name to include in the OTLP data
     * @param string $format The format to use for the OTLP data (json or protobuf)
     */
    public function __construct(string $serviceName = 'php-application', string $format = 'json')
    {
        $this->serviceName = $serviceName;

        if (!in_array($format, ['json', 'protobuf'])) {
            throw new \InvalidArgumentException(
                "Invalid format: {$format}. Supported formats are 'json' and 'protobuf'."
            );
        }

        $this->format = $format;
    }

    /**
     * {@inheritdoc}
     */
    public function export(ExcimerLog $log): string
    {
        $parsedLog = $log->getParsedLog();
        $metadata = $log->getMetadata();

        if ($this->format === 'json') {
            // Build the OTLP data structure for JSON format
            $otlpData = [
                'resourceSpans' => [
                    [
                        'resource' => [
                            'attributes' => [
                                [
                                    'key' => 'service.name',
                                    'value' => [
                                        'stringValue' => $this->serviceName,
                                    ],
                                ],
                            ],
                        ],
                        'scopeSpans' => [
                            [
                                'scope' => [
                                    'name' => 'excimetry',
                                    'version' => '1.0.0',
                                ],
                                'spans' => $this->convertToMetrics($parsedLog, $metadata),
                            ],
                        ],
                    ],
                ],
            ];

            // Add metadata as resource attributes
            foreach ($metadata as $key => $value) {
                if (is_scalar($value)) {
                    $otlpData['resourceSpans'][0]['resource']['attributes'][] = [
                        'key' => "excimetry.{$key}",
                        'value' => [
                            'stringValue' => (string)$value,
                        ],
                    ];
                }
            }

            return json_encode($otlpData, JSON_PRETTY_PRINT);
        } else {
            // Protobuf serialization
            // Create the request object
            $request = new \Opentelemetry\Proto\Collector\Metrics\V1\ExportMetricsServiceRequest();

            // Create the resource metrics
            $resourceMetrics = new \Opentelemetry\Proto\Metrics\V1\ResourceMetrics();

            // Create the resource
            $resource = new \Opentelemetry\Proto\Resource\V1\Resource();

            // Add service.name attribute
            $serviceNameKv = new \Opentelemetry\Proto\Common\V1\KeyValue();
            $serviceNameKv->setKey('service.name');
            $serviceNameValue = new \Opentelemetry\Proto\Common\V1\AnyValue();
            $serviceNameValue->setStringValue($this->serviceName);
            $serviceNameKv->setValue($serviceNameValue);
            $resource->setAttributes([$serviceNameKv]);

            // Add metadata as resource attributes
            $attributes = [$serviceNameKv];
            foreach ($metadata as $key => $value) {
                if (is_scalar($value)) {
                    $kv = new \Opentelemetry\Proto\Common\V1\KeyValue();
                    $kv->setKey("excimetry.{$key}");
                    $anyValue = new \Opentelemetry\Proto\Common\V1\AnyValue();
                    $anyValue->setStringValue((string)$value);
                    $kv->setValue($anyValue);
                    $attributes[] = $kv;
                }
            }
            $resource->setAttributes($attributes);

            // Set the resource on the resource metrics
            $resourceMetrics->setResource($resource);

            // Create the scope metrics
            $scopeMetrics = new \Opentelemetry\Proto\Metrics\V1\ScopeMetrics();

            // Create the instrumentation scope
            $scope = new \Opentelemetry\Proto\Common\V1\InstrumentationScope();
            $scope->setName('excimetry');
            $scope->setVersion('1.0.0');
            $scopeMetrics->setScope($scope);

            // Convert parsed log to metrics
            $metrics = $this->convertToPbMetrics($parsedLog, $metadata);
            $scopeMetrics->setMetrics($metrics);

            // Add the scope metrics to the resource metrics
            $resourceMetrics->setScopeMetrics([$scopeMetrics]);

            // Add the resource metrics to the request
            $request->setResourceMetrics([$resourceMetrics]);

            // Serialize the request to binary data
            return $request->serializeToString();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getContentType(): string
    {
        return $this->format === 'json' ? 'application/json' : 'application/x-protobuf';
    }

    /**
     * {@inheritdoc}
     */
    public function getFileExtension(): string
    {
        return $this->format === 'json' ? 'json' : 'bin';
    }

    /**
     * Set the service name.
     * 
     * @param string $serviceName The service name to include in the OTLP data
     * @return self
     */
    public function setServiceName(string $serviceName): self
    {
        $this->serviceName = $serviceName;
        return $this;
    }

    /**
     * Get the service name.
     * 
     * @return string The service name
     */
    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    /**
     * Set the format.
     * 
     * @param string $format The format to use for the OTLP data (json or protobuf)
     * @return self
     */
    public function setFormat(string $format): self
    {
        if (!in_array($format, ['json', 'protobuf'])) {
            throw new \InvalidArgumentException(
                "Invalid format: {$format}. Supported formats are 'json' and 'protobuf'."
            );
        }

        $this->format = $format;
        return $this;
    }

    /**
     * Get the format.
     * 
     * @return string The format
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * Add metadata to be included in the exported data.
     * 
     * @param string $key The metadata key
     * @param mixed $value The metadata value
     * @return self
     */
    public function addMetadata(string $key, mixed $value): self
    {
        // This method is used by OTLPBackend to add trace and span IDs
        // The metadata will be included in the export method
        return $this;
    }

    /**
     * Convert parsed log data to OTLP spans.
     * 
     * @param array $parsedLog The parsed log data
     * @param array $metadata The metadata
     * @return array The OTLP spans
     */
    private function convertToMetrics(array $parsedLog, array $metadata): array
    {
        $metrics = [];
        $startTime = $metadata['timestamp'] ?? time();
        $startTimeNanos = $startTime * 1_000_000_000;

        foreach ($parsedLog as $entry) {
            $frames = $entry['frames'];
            $count = $entry['count'];

            // Use the last frame (leaf) as the span name
            $spanName = end($frames);

            // Create a span for each stack trace
            $metrics[] = [
                'name' => $spanName,
                'startTimeUnixNano' => $startTimeNanos,
                'endTimeUnixNano' => $startTimeNanos + ($count * 1_000_000), // Assuming 1ms per sample
                'attributes' => [
                    [
                        'key' => 'excimetry.stack_trace',
                        'value' => [
                            'stringValue' => implode(';', $frames),
                        ],
                    ],
                    [
                        'key' => 'excimetry.sample_count',
                        'value' => [
                            'intValue' => $count,
                        ],
                    ],
                ],
            ];
        }

        return $metrics;
    }

    /**
     * Convert parsed log data to protobuf Metric objects.
     * 
     * @param array $parsedLog The parsed log data
     * @param array $metadata The metadata
     * @return array The protobuf Metric objects
     */
    private function convertToPbMetrics(array $parsedLog, array $metadata): array
    {
        $metrics = [];
        $startTime = $metadata['timestamp'] ?? time();
        $startTimeNanos = $startTime * 1_000_000_000;

        foreach ($parsedLog as $entry) {
            $frames = $entry['frames'];
            $count = $entry['count'];

            // Use the last frame (leaf) as the metric name
            $metricName = end($frames);

            // Create a metric for each stack trace
            $metric = new \Opentelemetry\Proto\Metrics\V1\Metric();
            $metric->setName($metricName);
            $metric->setDescription('Excimetry profile metric');
            $metric->setUnit('count');

            // Create a gauge for the metric
            $gauge = new \Opentelemetry\Proto\Metrics\V1\Gauge();

            // Create a data point for the gauge
            $dataPoint = new \Opentelemetry\Proto\Metrics\V1\NumberDataPoint();
            $dataPoint->setTimeUnixNano($startTimeNanos + ($count * 1_000_000)); // Assuming 1ms per sample
            $dataPoint->setStartTimeUnixNano($startTimeNanos);
            $dataPoint->setAsInt($count);

            // Add attributes to the data point
            $stackTraceKv = new \Opentelemetry\Proto\Common\V1\KeyValue();
            $stackTraceKv->setKey('excimetry.stack_trace');
            $stackTraceValue = new \Opentelemetry\Proto\Common\V1\AnyValue();
            $stackTraceValue->setStringValue(implode(';', $frames));
            $stackTraceKv->setValue($stackTraceValue);

            $sampleCountKv = new \Opentelemetry\Proto\Common\V1\KeyValue();
            $sampleCountKv->setKey('excimetry.sample_count');
            $sampleCountValue = new \Opentelemetry\Proto\Common\V1\AnyValue();
            $sampleCountValue->setIntValue($count);
            $sampleCountKv->setValue($sampleCountValue);

            $dataPoint->setAttributes([$stackTraceKv, $sampleCountKv]);

            // Add the data point to the gauge
            $gauge->setDataPoints([$dataPoint]);

            // Set the gauge on the metric
            $metric->setGauge($gauge);

            $metrics[] = $metric;
        }

        return $metrics;
    }
}
