<?php

declare(strict_types=1);

namespace Excimetry\Backend;

use Excimetry\Profiler\ExcimerLog;
use Excimetry\Exporter\ExporterInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

/**
 * Backend for sending profile data to a remote server via HTTP.
 */
class HttpBackend extends AbstractBackend
{
    /**
     * @var Client The Guzzle HTTP client
     */
    protected Client $client;
    /**
     * @var string The URL to send the data to
     */
    private string $url;

    /**
     * @var array Additional headers to include in the request
     */
    private array $headers = [];

    /**
     * @var int The timeout for the request in seconds
     */
    private int $timeout = 30;

    /**
     * Create a new HttpBackend instance.
     * 
     * @param ExporterInterface $exporter The exporter to use
     * @param string $url The URL to send the data to
     * @param array $headers Additional headers to include in the request
     */
    public function __construct(
        ExporterInterface $exporter,
        string $url,
        array $headers = []
    ) {
        parent::__construct($exporter);

        $this->url = $url;
        $this->headers = $headers;
        $this->client = new Client([
            'timeout' => $this->timeout,
            'headers' => $this->headers,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function send(ExcimerLog $log): bool
    {
        if ($this->async) {
            // Send asynchronously
            $this->sendAsync($log);
            return true;
        }

        return $this->sendWithRetries($log);
    }

    /**
     * {@inheritdoc}
     */
    protected function doSend(ExcimerLog $log): bool
    {
        // Export the data
        $data = $this->exporter->export($log);

        // Prepare headers
        $headers = [
            'Content-Type' => $this->exporter->getContentType(),
        ];

        try {
            // Execute the request using Guzzle
            $response = $this->client->post($this->url, [
                RequestOptions::BODY => $data,
                RequestOptions::HEADERS => $headers,
                RequestOptions::TIMEOUT => $this->timeout,
            ]);

            // Check the status code
            $statusCode = $response->getStatusCode();
            if ($statusCode < 200 || $statusCode >= 300) {
                error_log("HTTP request failed with status code {$statusCode}: {$response->getBody()}");
                return false;
            }

            return true;
        } catch (GuzzleException $e) {
            error_log("HTTP request failed: {$e->getMessage()}");
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
        $data = $this->exporter->export($log);

        // Prepare headers
        $headers = [
            'Content-Type' => $this->exporter->getContentType(),
        ];

        // Send the request asynchronously
        $this->client->postAsync($this->url, [
            RequestOptions::BODY => $data,
            RequestOptions::HEADERS => $headers,
            RequestOptions::TIMEOUT => $this->timeout,
        ])->then(
            function ($response) {
                // Request succeeded
                $statusCode = $response->getStatusCode();
                if ($statusCode < 200 || $statusCode >= 300) {
                    error_log("Async HTTP request failed with status code {$statusCode}: {$response->getBody()}");
                }
            },
            function ($exception) {
                // Request failed
                error_log("Async HTTP request failed: {$exception->getMessage()}");
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable(): bool
    {
        try {
            // Check if the URL is reachable using Guzzle
            $response = $this->client->head($this->url, [
                RequestOptions::TIMEOUT => 5,
            ]);

            return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
        } catch (GuzzleException $e) {
            return false;
        }
    }

    /**
     * Set the URL to send the data to.
     * 
     * @param string $url The URL
     * @return self
     */
    public function setUrl(string $url): self
    {
        $this->url = $url;
        // Recreate the client with the new URL
        $this->client = new Client([
            'timeout' => $this->timeout,
            'headers' => $this->headers,
        ]);
        return $this;
    }

    /**
     * Get the URL to send the data to.
     * 
     * @return string The URL
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Set the headers to include in the request.
     * 
     * @param array $headers The headers
     * @return self
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;
        // Update the client's headers
        $this->client = new Client([
            'timeout' => $this->timeout,
            'headers' => $this->headers,
        ]);
        return $this;
    }

    /**
     * Get the headers to include in the request.
     * 
     * @return array The headers
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Set the timeout for the request.
     * 
     * @param int $timeout The timeout in seconds
     * @return self
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        // Update the client's timeout setting
        $this->client = new Client([
            'timeout' => $this->timeout,
            'headers' => $this->headers,
        ]);
        return $this;
    }

    /**
     * Get the timeout for the request.
     * 
     * @return int The timeout in seconds
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }
}
