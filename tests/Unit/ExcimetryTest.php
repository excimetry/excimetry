<?php

declare(strict_types=1);

namespace Excimetry\Tests\Unit;

use Excimetry\Excimetry;
use Excimetry\ExcimetryConfig;
use Excimetry\Exporter\ExporterInterface;
use Excimetry\Exporter\CollapsedExporter;
use Excimetry\Exporter\SpeedscopeExporter;
use Excimetry\Exporter\OTLPExporter;
use Excimetry\Backend\BackendInterface;
use Excimetry\Backend\FileBackend;
use Excimetry\Profiler\ExcimerLog;
use Excimetry\Profiler\ExcimerProfiler;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Excimetry class.
 */
final class ExcimetryTest extends TestCase
{
    /**
     * Test creating a new Excimetry instance with default configuration.
     */
    public function testConstructorWithDefaultConfig(): void
    {
        $excimetry = new Excimetry();

        $this->assertInstanceOf(Excimetry::class, $excimetry);
        $this->assertInstanceOf(ExcimerProfiler::class, $excimetry->getProfiler());
        $this->assertInstanceOf(ExcimetryConfig::class, $excimetry->getConfig());
    }

    /**
     * Test creating a new Excimetry instance with a configuration array.
     */
    public function testConstructorWithConfigArray(): void
    {
        $excimetry = new Excimetry([
            'period' => 0.5,
            'mode' => 'cpu',
        ]);

        $this->assertInstanceOf(Excimetry::class, $excimetry);
        $this->assertInstanceOf(ExcimerProfiler::class, $excimetry->getProfiler());
        $this->assertInstanceOf(ExcimetryConfig::class, $excimetry->getConfig());

        $config = $excimetry->getConfig();
        $this->assertSame(0.5, $config->getPeriod());
        $this->assertSame('cpu', $config->getMode());
    }

    /**
     * Test creating a new Excimetry instance with a configuration object.
     */
    public function testConstructorWithConfigObject(): void
    {
        $config = new ExcimetryConfig([
            'period' => 0.5,
            'mode' => 'cpu',
        ]);

        $excimetry = new Excimetry($config);

        $this->assertInstanceOf(Excimetry::class, $excimetry);
        $this->assertInstanceOf(ExcimerProfiler::class, $excimetry->getProfiler());
        $this->assertSame($config, $excimetry->getConfig());
    }

    /**
     * Test creating a new Excimetry instance with the static create method.
     */
    public function testCreate(): void
    {
        $excimetry = Excimetry::create();

        $this->assertInstanceOf(Excimetry::class, $excimetry);
        $this->assertInstanceOf(ExcimerProfiler::class, $excimetry->getProfiler());
        $this->assertInstanceOf(ExcimetryConfig::class, $excimetry->getConfig());
    }

    /**
     * Test starting and stopping profiling.
     */
    public function testStartAndStop(): void
    {
        $excimetry = new Excimetry();

        // Start profiling
        $result = $excimetry->start();

        // Check that the method returns $this for chaining
        $this->assertSame($excimetry, $result);

        // Check that the profiler is running
        $this->assertTrue($excimetry->isRunning());

        // Stop profiling
        $result = $excimetry->stop();

        // Check that the method returns $this for chaining
        $this->assertSame($excimetry, $result);

        // Check that the profiler is not running
        $this->assertFalse($excimetry->isRunning());

        // Check that we can get the log
        $log = $excimetry->getLog();
        $this->assertInstanceOf(ExcimerLog::class, $log);
    }

    /**
     * Test resetting the profiler.
     */
    public function testReset(): void
    {
        $excimetry = new Excimetry();

        // Start and stop profiling
        $excimetry->start()->stop();

        // Check that we can get the log
        $this->assertInstanceOf(ExcimerLog::class, $excimetry->getLog());

        // Reset the profiler
        $result = $excimetry->reset();

        // Check that the method returns $this for chaining
        $this->assertSame($excimetry, $result);

        // Check that the profiler is not running
        $this->assertFalse($excimetry->isRunning());

        // Check that we can't get the log anymore
        $this->expectException(\RuntimeException::class);
        $excimetry->getLog();
    }

    /**
     * Test exporting the profiling data.
     */
    public function testExport(): void
    {
        $excimetry = new Excimetry();

        // Start and stop profiling
        $excimetry->start()->stop();

        // Create a mock exporter
        $exporter = $this->createMock(ExporterInterface::class);
        $exporter->expects($this->once())
            ->method('export')
            ->with($this->isInstanceOf(ExcimerLog::class))
            ->willReturn('exported data');

        // Export the data
        $result = $excimetry->export($exporter);

        // Check the result
        $this->assertSame('exported data', $result);
    }

    /**
     * Test exporting without stopping profiling first.
     */
    public function testExportWithoutStopping(): void
    {
        $excimetry = new Excimetry();

        // Start profiling but don't stop
        $excimetry->start();

        // Create a mock exporter
        $exporter = $this->createMock(ExporterInterface::class);

        // Try to export the data
        $this->expectException(\RuntimeException::class);
        $excimetry->export($exporter);
    }

    /**
     * Test setting the sampling period.
     */
    public function testSetPeriod(): void
    {
        $excimetry = new Excimetry();

        // Set the period
        $result = $excimetry->setPeriod(0.5);

        // Check that the method returns $this for chaining
        $this->assertSame($excimetry, $result);
    }

    /**
     * Test setting the profiling mode.
     */
    public function testSetMode(): void
    {
        $excimetry = new Excimetry();

        // Set the mode
        $result = $excimetry->setMode('cpu');

        // Check that the method returns $this for chaining
        $this->assertSame($excimetry, $result);
    }

    /**
     * Test adding metadata.
     */
    public function testAddMetadata(): void
    {
        $excimetry = new Excimetry();

        // Add metadata
        $result = $excimetry->addMetadata('key', 'value');

        // Check that the method returns $this for chaining
        $this->assertSame($excimetry, $result);
    }

    /**
     * Test setting multiple metadata values.
     */
    public function testSetMetadata(): void
    {
        $excimetry = new Excimetry();

        // Set metadata
        $result = $excimetry->setMetadata(['key1' => 'value1', 'key2' => 'value2']);

        // Check that the method returns $this for chaining
        $this->assertSame($excimetry, $result);
    }

    /**
     * Test creating an exporter based on the configuration.
     */
    public function testCreateExporter(): void
    {
        // Test with speedscope format (default)
        $config = new ExcimetryConfig(['exportFormat' => 'speedscope']);
        $excimetry = new Excimetry($config);

        $exporter = $excimetry->createExporter();
        $this->assertInstanceOf(SpeedscopeExporter::class, $exporter);

        // Test with collapsed format
        $config = new ExcimetryConfig(['exportFormat' => 'collapsed']);
        $excimetry = new Excimetry($config);

        $exporter = $excimetry->createExporter();
        $this->assertInstanceOf(CollapsedExporter::class, $exporter);

        // Test with OTLP format
        $config = new ExcimetryConfig(['exportFormat' => 'otlp']);
        $excimetry = new Excimetry($config);

        $exporter = $excimetry->createExporter();
        $this->assertInstanceOf(OTLPExporter::class, $exporter);
    }
}
