<?php

declare(strict_types=1);

namespace Excimetry\Tests\Unit\Profiler;

use Excimetry\Profiler\ExcimerProfiler;
use Excimetry\Profiler\ExcimerLog;
use Excimetry\Tests\Mock\ExcimerProfilerMock;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Unit tests for the ExcimerProfiler class.
 */
final class ExcimerProfilerTest extends TestCase
{
    /**
     * @var ExcimerProfilerMock The mock ExcimerProfiler instance
     */
    private ExcimerProfilerMock $nativeProfiler;
    
    /**
     * @var ExcimerProfiler The ExcimerProfiler instance under test
     */
    private ExcimerProfiler $profiler;
    
    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a mock native ExcimerProfiler
        $this->nativeProfiler = new ExcimerProfilerMock();
        
        // Create the ExcimerProfiler instance and inject the mock
        $this->profiler = new ExcimerProfiler();
        
        // Use reflection to replace the native profiler with our mock
        $reflection = new ReflectionClass($this->profiler);
        $property = $reflection->getProperty('profiler');
        $property->setAccessible(true);
        $property->setValue($this->profiler, $this->nativeProfiler);
    }
    
    /**
     * Test creating a new ExcimerProfiler instance with default options.
     */
    public function testConstructWithDefaults(): void
    {
        $profiler = new ExcimerProfiler();
        
        // Use reflection to get the private properties
        $reflection = new ReflectionClass($profiler);
        
        $periodProperty = $reflection->getProperty('period');
        $periodProperty->setAccessible(true);
        $period = $periodProperty->getValue($profiler);
        
        $modeProperty = $reflection->getProperty('mode');
        $modeProperty->setAccessible(true);
        $mode = $modeProperty->getValue($profiler);
        
        $runningProperty = $reflection->getProperty('running');
        $runningProperty->setAccessible(true);
        $running = $runningProperty->getValue($profiler);
        
        $metadataProperty = $reflection->getProperty('metadata');
        $metadataProperty->setAccessible(true);
        $metadata = $metadataProperty->getValue($profiler);
        
        // Check the default values
        $this->assertSame(0.01, $period);
        $this->assertSame('wall', $mode);
        $this->assertFalse($running);
        $this->assertSame([], $metadata);
    }
    
    /**
     * Test creating a new ExcimerProfiler instance with custom options.
     */
    public function testConstructWithOptions(): void
    {
        $options = [
            'period' => 0.05,
            'mode' => 'cpu',
            'metadata' => ['foo' => 'bar'],
        ];
        
        $profiler = new ExcimerProfiler($options);
        
        // Use reflection to get the private properties
        $reflection = new ReflectionClass($profiler);
        
        $periodProperty = $reflection->getProperty('period');
        $periodProperty->setAccessible(true);
        $period = $periodProperty->getValue($profiler);
        
        $modeProperty = $reflection->getProperty('mode');
        $modeProperty->setAccessible(true);
        $mode = $modeProperty->getValue($profiler);
        
        $metadataProperty = $reflection->getProperty('metadata');
        $metadataProperty->setAccessible(true);
        $metadata = $metadataProperty->getValue($profiler);
        
        // Check the custom values
        $this->assertSame(0.05, $period);
        $this->assertSame('cpu', $mode);
        $this->assertSame(['foo' => 'bar'], $metadata);
    }
    
    /**
     * Test setting the sampling period.
     */
    public function testSetPeriod(): void
    {
        $this->profiler->setPeriod(0.05);
        
        // Check that the period was set on the native profiler
        $this->assertTrue($this->nativeProfiler->wasCalled('setPeriod'));
        
        // Use reflection to check that the period was set on the wrapper
        $reflection = new ReflectionClass($this->profiler);
        $property = $reflection->getProperty('period');
        $property->setAccessible(true);
        $period = $property->getValue($this->profiler);
        
        $this->assertSame(0.05, $period);
    }
    
    /**
     * Test setting an invalid profiling mode.
     */
    public function testSetInvalidMode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->profiler->setMode('invalid');
    }
    
    /**
     * Test starting the profiler.
     */
    public function testStart(): void
    {
        $this->profiler->start();
        
        // Check that the start method was called on the native profiler
        $this->assertTrue($this->nativeProfiler->wasCalled('start'));
        
        // Check that the running flag was set
        $this->assertTrue($this->profiler->isRunning());
    }
    
    /**
     * Test stopping the profiler.
     */
    public function testStop(): void
    {
        // First start the profiler
        $this->profiler->start();
        
        // Then stop it
        $this->profiler->stop();
        
        // Check that the stop method was called on the native profiler
        $this->assertTrue($this->nativeProfiler->wasCalled('stop'));
        
        // Check that the running flag was cleared
        $this->assertFalse($this->profiler->isRunning());
    }
    
    /**
     * Test stopping the profiler when it's not running.
     */
    public function testStopWhenNotRunning(): void
    {
        // The profiler is not running initially
        $this->profiler->stop();
        
        // Check that the stop method was not called on the native profiler
        $this->assertFalse($this->nativeProfiler->wasCalled('stop'));
    }
    
    /**
     * Test resetting the profiler.
     */
    public function testReset(): void
    {
        // First start the profiler
        $this->profiler->start();
        
        // Set a custom period
        $this->profiler->setPeriod(0.05);
        
        // Then reset it
        $this->profiler->reset();
        
        // Check that the stop method was called on the native profiler
        $this->assertTrue($this->nativeProfiler->wasCalled('stop'));
        
        // Check that the running flag was cleared
        $this->assertFalse($this->profiler->isRunning());
        
        // Check that a new native profiler was created
        // This is hard to test directly, but we can check that the period was set again
        $this->assertGreaterThanOrEqual(2, $this->nativeProfiler->getCallCount('setPeriod'));
    }
    
    /**
     * Test getting the log.
     */
    public function testGetLog(): void
    {
        // Set up the mock to return a sample log
        $sampleLog = "main;App\Controller\HomeController;index 1";
        $this->nativeProfiler->setRawLog($sampleLog);
        
        // Get the log
        $log = $this->profiler->getLog();
        
        // Check that the getLog method was called on the native profiler
        $this->assertTrue($this->nativeProfiler->wasCalled('getLog'));
        
        // Check that an ExcimerLog instance was returned
        $this->assertInstanceOf(ExcimerLog::class, $log);
        
        // Check that the log contains the sample data
        $this->assertSame($sampleLog, $log->getRawLog());
        
        // Check that the metadata includes standard fields
        $metadata = $log->getMetadata();
        $this->assertArrayHasKey('timestamp', $metadata);
        $this->assertArrayHasKey('period', $metadata);
        $this->assertArrayHasKey('mode', $metadata);
        $this->assertArrayHasKey('php_version', $metadata);
        $this->assertArrayHasKey('os', $metadata);
    }
    
    /**
     * Test adding metadata.
     */
    public function testAddMetadata(): void
    {
        $this->profiler->addMetadata('foo', 'bar');
        
        // Use reflection to check that the metadata was added
        $reflection = new ReflectionClass($this->profiler);
        $property = $reflection->getProperty('metadata');
        $property->setAccessible(true);
        $metadata = $property->getValue($this->profiler);
        
        $this->assertArrayHasKey('foo', $metadata);
        $this->assertSame('bar', $metadata['foo']);
    }
    
    /**
     * Test setting multiple metadata values.
     */
    public function testSetMetadata(): void
    {
        $metadata = ['foo' => 'bar', 'baz' => 'qux'];
        $this->profiler->setMetadata($metadata);
        
        // Use reflection to check that the metadata was set
        $reflection = new ReflectionClass($this->profiler);
        $property = $reflection->getProperty('metadata');
        $property->setAccessible(true);
        $actualMetadata = $property->getValue($this->profiler);
        
        $this->assertSame($metadata, $actualMetadata);
    }
    
    /**
     * Test getting the metadata.
     */
    public function testGetMetadata(): void
    {
        $metadata = ['foo' => 'bar', 'baz' => 'qux'];
        $this->profiler->setMetadata($metadata);
        
        $this->assertSame($metadata, $this->profiler->getMetadata());
    }
}