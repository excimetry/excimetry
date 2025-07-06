<?php

declare(strict_types=1);

namespace Excimetry\Tests\Unit\Backend;

use Excimetry\Backend\PyroscopeBackend;
use Excimetry\Exporter\CollapsedExporter;
use Excimetry\Tests\Mock\ExporterMock;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Unit tests for the PyroscopeBackend class.
 * 
 * Note: This test focuses on the unique aspects of PyroscopeBackend without
 * actually making HTTP requests.
 */
final class PyroscopeBackendTest extends TestCase
{
    /**
     * Test creating a new PyroscopeBackend instance with default options.
     */
    public function testConstructWithDefaults(): void
    {
        $exporter = new ExporterMock();
        $backend = new PyroscopeBackend('https://pyroscope.example.com', 'test-app');

        // Check that the URL is correctly constructed
        $this->assertSame('https://pyroscope.example.com/ingest', $backend->getUrl());

        // Check that the app name is set
        $this->assertSame('test-app', $backend->getAppName());

        // Check that the labels are empty
        $this->assertSame([], $backend->getLabels());

        // Check that the exporter is a CollapsedExporter
        $this->assertInstanceOf(CollapsedExporter::class, $backend->getExporter());
    }

    /**
     * Test creating a new PyroscopeBackend instance with custom options.
     */
    public function testConstructWithCustomOptions(): void
    {
        $exporter = new ExporterMock();
        $labels = ['env' => 'test', 'version' => '1.0.0'];
        $backend = new PyroscopeBackend('https://pyroscope.example.com', 'test-app', $labels, $exporter);

        // Check that the URL is correctly constructed
        $this->assertSame('https://pyroscope.example.com/ingest', $backend->getUrl());

        // Check that the app name is set
        $this->assertSame('test-app', $backend->getAppName());

        // Check that the labels are set
        $this->assertSame($labels, $backend->getLabels());

        // Check that the exporter is the one we provided
        $this->assertSame($exporter, $backend->getExporter());
    }

    /**
     * Test setting and getting the application name.
     */
    public function testSetAndGetAppName(): void
    {
        $backend = new PyroscopeBackend('https://pyroscope.example.com', 'test-app');

        $this->assertSame('test-app', $backend->getAppName());

        $backend->setAppName('new-app');

        $this->assertSame('new-app', $backend->getAppName());
    }

    /**
     * Test setting and getting the labels.
     */
    public function testSetAndGetLabels(): void
    {
        $backend = new PyroscopeBackend('https://pyroscope.example.com', 'test-app');

        $this->assertSame([], $backend->getLabels());

        $labels = ['env' => 'test', 'version' => '1.0.0'];
        $backend->setLabels($labels);

        $this->assertSame($labels, $backend->getLabels());
    }

    /**
     * Test adding a label.
     */
    public function testAddLabel(): void
    {
        $backend = new PyroscopeBackend('https://pyroscope.example.com', 'test-app');

        $this->assertSame([], $backend->getLabels());

        $backend->addLabel('env', 'test');

        $this->assertSame(['env' => 'test'], $backend->getLabels());

        $backend->addLabel('version', '1.0.0');

        $this->assertSame(['env' => 'test', 'version' => '1.0.0'], $backend->getLabels());
    }

    /**
     * Test that the server URL is normalized.
     */
    public function testServerUrlIsNormalized(): void
    {
        // Test with trailing slash
        $backend1 = new PyroscopeBackend('https://pyroscope.example.com/', 'test-app');
        $this->assertSame('https://pyroscope.example.com/ingest', $backend1->getUrl());

        // Test without trailing slash
        $backend2 = new PyroscopeBackend('https://pyroscope.example.com', 'test-app');
        $this->assertSame('https://pyroscope.example.com/ingest', $backend2->getUrl());
    }

    /**
     * Test that the code for building the URL with query parameters is correct.
     */
    public function testUrlBuildingCode(): void
    {
        // Create a backend with labels
        $labels = ['env' => 'test', 'version' => '1.0.0'];
        $backend = new PyroscopeBackend('https://pyroscope.example.com', 'test-app', $labels);

        // Get the source code of the class
        $reflection = new ReflectionClass($backend);
        $sourceCode = file_get_contents($reflection->getFileName());

        // Check that the URL building code is correct
        $expectedBaseUrl = 'https://pyroscope.example.com/ingest';
        $urlPattern = preg_quote('$url = $this->getUrl() . \'?\' . http_build_query($params);', '/');
        $this->assertMatchesRegularExpression('/' . $urlPattern . '/', $sourceCode);

        // Check that the labels are formatted correctly
        $labelsPattern = 'if \(!empty\(\$this->labels\)\) \{.*?\$labelString \.\= "\{\$key\}=\{\$value\}";.*?\}';
        $this->assertMatchesRegularExpression('/' . $labelsPattern . '/s', $sourceCode);

        // Check that the parameters include name, from, and until
        $paramsPattern = '\$params = \[.*?\'name\' => \$this->appName.*?\'from\' => \$log->getMetadata\(\)\[\'timestamp\'\] \?\? time\(\).*?\'until\' => time\(\).*?\];';
        $this->assertMatchesRegularExpression('/' . $paramsPattern . '/s', $sourceCode);
    }
}
