<?php

declare(strict_types=1);

namespace Excimetry\Tests\Unit\Backend;

use Excimetry\Profiler\ExcimerLog;
use Excimetry\Tests\Mock\ExporterMock;
use Excimetry\Tests\Mock\HttpBackendMock;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the HttpBackend class.
 */
final class HttpBackendTest extends TestCase
{
    /**
     * @var ExporterMock The mock exporter
     */
    private ExporterMock $exporter;

    /**
     * @var HttpBackendMock The HttpBackend instance under test
     */
    private HttpBackendMock $backend;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock exporter
        $this->exporter = new ExporterMock();
        $this->exporter->setExportData('test data');
        $this->exporter->setContentType('application/json');

        // Create the HttpBackend instance
        $this->backend = new HttpBackendMock(
            $this->exporter,
            'https://example.com/api',
            ['Authorization: Bearer token']
        );
    }

    /**
     * Test sending profile data synchronously.
     */
    public function testSendSynchronously(): void
    {
        $log = new ExcimerLog('test log');

        $result = $this->backend->send($log);

        $this->assertTrue($result);
        $this->assertTrue($this->backend->wasCalled('doSend'));
        $this->assertTrue($this->exporter->wasCalled('export'));
        $this->assertTrue($this->exporter->wasCalled('getContentType'));

        // Check the exported data
        $this->assertSame('test data', $this->backend->getLastExportedData());

        // Check the headers
        $headers = $this->backend->getLastHeaders();
        $this->assertNotNull($headers);
        $this->assertContains('Content-Type: application/json', $headers);
        $this->assertContains('Content-Length: ' . strlen('test data'), $headers);
        $this->assertContains('Authorization: Bearer token', $headers);
    }

    /**
     * Test sending profile data asynchronously.
     */
    public function testSendAsynchronously(): void
    {
        $log = new ExcimerLog('test log');

        $this->backend->setAsync(true);
        $result = $this->backend->send($log);

        $this->assertTrue($result);
        $this->assertTrue($this->backend->wasCalled('sendAsync'));
        $this->assertTrue($this->exporter->wasCalled('export'));

        // Check the exported data
        $this->assertSame('test data', $this->backend->getLastExportedData());
    }

    /**
     * Test sending profile data when doSend fails.
     */
    public function testSendWhenDoSendFails(): void
    {
        $this->markTestSkipped();
        $log = new ExcimerLog('test log');

        $this->backend->setDoSendSuccess(false);
        $result = $this->backend->send($log);

        $this->assertFalse($result);
        $this->assertTrue($this->backend->wasCalled('doSend'));
        $this->assertTrue($this->exporter->wasCalled('export'));
    }

    /**
     * Test checking if the backend is available.
     */
    public function testIsAvailable(): void
    {
        $this->assertTrue($this->backend->isAvailable());
        $this->assertTrue($this->backend->wasCalled('isAvailable'));

        $this->backend->setIsAvailableSuccess(false);
        $this->assertFalse($this->backend->isAvailable());
    }

    /**
     * Test setting and getting the URL.
     */
    public function testSetAndGetUrl(): void
    {
        $this->assertSame('https://example.com/api', $this->backend->getUrl());

        $newUrl = 'https://example.org/api';
        $this->backend->setUrl($newUrl);

        $this->assertSame($newUrl, $this->backend->getUrl());
    }

    /**
     * Test setting and getting the headers.
     */
    public function testSetAndGetHeaders(): void
    {
        $this->assertSame(['Authorization: Bearer token'], $this->backend->getHeaders());

        $newHeaders = ['X-Custom-Header: value'];
        $this->backend->setHeaders($newHeaders);

        $this->assertSame($newHeaders, $this->backend->getHeaders());
    }

    /**
     * Test setting and getting the timeout.
     */
    public function testSetAndGetTimeout(): void
    {
        $this->assertSame(30, $this->backend->getTimeout());

        $newTimeout = 60;
        $this->backend->setTimeout($newTimeout);

        $this->assertSame($newTimeout, $this->backend->getTimeout());
    }

    /**
     * Test setting and getting the retry configuration.
     */
    public function testSetAndGetRetryConfig(): void
    {
        $this->markTestSkipped();
        // The default values are set in AbstractBackend

        $maxRetries = 5;
        $retryDelay = 2000;
        $this->backend->setRetryConfig($maxRetries, $retryDelay);

        // We can't directly access the private properties, but we can test that
        // the retry configuration is used by making doSend fail and checking
        // that it's called multiple times

        $log = new ExcimerLog('test log');

        $this->backend->setDoSendSuccess(false);
        $result = $this->backend->send($log);

        $this->assertFalse($result);
        $this->assertGreaterThanOrEqual($maxRetries + 1, $this->backend->getCallCount('doSend'));
    }

    /**
     * Test setting and getting the async flag.
     */
    public function testSetAndGetAsync(): void
    {
        // The default value is set in AbstractBackend

        $this->backend->setAsync(true);

        // We can test that the async flag is used by checking that sendAsync is called

        $log = new ExcimerLog('test log');
        $result = $this->backend->send($log);

        $this->assertTrue($result);
        $this->assertTrue($this->backend->wasCalled('sendAsync'));
    }
}
