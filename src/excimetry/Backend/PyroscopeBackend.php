<?php

declare(strict_types=1);

namespace Excimetry\Backend;

use Excimetry\Profiler\ExcimerLog;
use Excimetry\Exporter\CollapsedExporter;
use Excimetry\Exporter\ExporterInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

/**
 * Backend for sending profile data to Pyroscope.
 */
final class PyroscopeBackend extends HttpBackend
{
    /**
     * @var string The application name to use in Pyroscope
     */
    private string $appName;

    /**
     * @var array Labels to include with the profile
     */
    private array $labels = [];

    /**
     * Create a new PyroscopeBackend instance.
     * 
     * @param string $serverUrl The URL of the Pyroscope server
     * @param string $appName The application name to use in Pyroscope
     * @param array $labels Labels to include with the profile
     * @param ExporterInterface|null $exporter The exporter to use, or null to use CollapsedExporter
     */
    public function __construct(
        string $serverUrl,
        string $appName,
        array $labels = [],
        ?ExporterInterface $exporter = null
    ) {
        // Use CollapsedExporter by default
        if ($exporter === null) {
            $exporter = new CollapsedExporter();
        }

        // Build the ingest URL
        $url = rtrim($serverUrl, '/') . '/ingest';

        parent::__construct($exporter, $url);

        $this->appName = $appName;
        $this->labels = $labels;
    }

    /**
     * {@inheritdoc}
     */
    protected function doSend(ExcimerLog $log): bool
    {
        // Export the data
        $data = $this->getExporter()->export($log);

        // Build the query parameters
        $params = [
            'name' => $this->appName,
            'from' => $log->getMetadata()['timestamp'] ?? time(),
            'until' => time(),
        ];

        // Add labels
        if (!empty($this->labels)) {
            $labelString = '';
            foreach ($this->labels as $key => $value) {
                if (!empty($labelString)) {
                    $labelString .= ',';
                }
                $labelString .= "{$key}={$value}";
            }
            $params['labels'] = $labelString;
        }

        // Build the URL with query parameters
        $url = $this->getUrl() . '?' . http_build_query($params);

        // Prepare headers
        $headers = [
            'Content-Type' => $this->getExporter()->getContentType(),
        ];

        try {
            // Execute the request using Guzzle
            $response = $this->client->post($url, [
                RequestOptions::BODY => $data,
                RequestOptions::HEADERS => $headers,
                RequestOptions::TIMEOUT => $this->getTimeout(),
            ]);

            // Check the status code
            $statusCode = $response->getStatusCode();
            if ($statusCode < 200 || $statusCode >= 300) {
                error_log("Pyroscope request failed with status code {$statusCode}: {$response->getBody()}");
                return false;
            }

            return true;
        } catch (GuzzleException $e) {
            error_log("Pyroscope request failed: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Send the profile data asynchronously.
     * 
     * @param ExcimerLog $log The profile data to send
     */
    protected function sendAsync(ExcimerLog $log): void
    {
        // Export the data
        $data = $this->getExporter()->export($log);

        // Build the query parameters
        $params = [
            'name' => $this->appName,
            'from' => $log->getMetadata()['timestamp'] ?? time(),
            'until' => time(),
        ];

        // Add labels
        if (!empty($this->labels)) {
            $labelString = '';
            foreach ($this->labels as $key => $value) {
                if (!empty($labelString)) {
                    $labelString .= ',';
                }
                $labelString .= "{$key}={$value}";
            }
            $params['labels'] = $labelString;
        }

        // Build the URL with query parameters
        $url = $this->getUrl() . '?' . http_build_query($params);

        // Prepare headers
        $headers = [
            'Content-Type' => $this->getExporter()->getContentType(),
        ];

        // Send the request asynchronously
        $this->client->postAsync($url, [
            RequestOptions::BODY => $data,
            RequestOptions::HEADERS => $headers,
            RequestOptions::TIMEOUT => $this->getTimeout(),
        ])->then(
            function ($response) {
                // Request succeeded
                $statusCode = $response->getStatusCode();
                if ($statusCode < 200 || $statusCode >= 300) {
                    error_log("Async Pyroscope request failed with status code {$statusCode}: {$response->getBody()}");
                }
            },
            function ($exception) {
                // Request failed
                error_log("Async Pyroscope request failed: {$exception->getMessage()}");
            }
        );
    }

    /**
     * Set the application name to use in Pyroscope.
     * 
     * @param string $appName The application name
     * @return self
     */
    public function setAppName(string $appName): self
    {
        $this->appName = $appName;
        return $this;
    }

    /**
     * Get the application name to use in Pyroscope.
     * 
     * @return string The application name
     */
    public function getAppName(): string
    {
        return $this->appName;
    }

    /**
     * Set the labels to include with the profile.
     * 
     * @param array $labels The labels
     * @return self
     */
    public function setLabels(array $labels): self
    {
        $this->labels = $labels;
        return $this;
    }

    /**
     * Get the labels to include with the profile.
     * 
     * @return array The labels
     */
    public function getLabels(): array
    {
        return $this->labels;
    }

    /**
     * Add a label to include with the profile.
     * 
     * @param string $key The label key
     * @param string $value The label value
     * @return self
     */
    public function addLabel(string $key, string $value): self
    {
        $this->labels[$key] = $value;
        return $this;
    }
}
