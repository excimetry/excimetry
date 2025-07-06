<?php

declare(strict_types=1);

namespace Excimetry\Tests\Unit;

use Excimetry\ExcimetryConfig;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the ExcimetryConfig class.
 */
final class ExcimetryConfigTest extends TestCase
{
    /**
     * Test creating a new ExcimetryConfig instance with default values.
     */
    public function testConstructorWithDefaults(): void
    {
        $config = new ExcimetryConfig();
        
        $this->assertSame(0.01, $config->getPeriod());
        $this->assertSame('wall', $config->getMode());
        $this->assertSame([], $config->getMetadata());
        $this->assertSame('speedscope', $config->getExportFormat());
        $this->assertSame('profiles', $config->getOutputDirectory());
        $this->assertFalse($config->isAsyncExport());
        $this->assertSame(3, $config->getMaxRetries());
        $this->assertSame(1000, $config->getRetryDelay());
    }
    
    /**
     * Test creating a new ExcimetryConfig instance with custom values.
     */
    public function testConstructorWithCustomValues(): void
    {
        $config = new ExcimetryConfig([
            'period' => 0.5,
            'mode' => 'cpu',
            'metadata' => ['key' => 'value'],
            'exportFormat' => 'collapsed',
            'outputDirectory' => '/tmp/profiles',
            'asyncExport' => true,
            'maxRetries' => 5,
            'retryDelay' => 2000,
        ]);
        
        $this->assertSame(0.5, $config->getPeriod());
        $this->assertSame('cpu', $config->getMode());
        $this->assertSame(['key' => 'value'], $config->getMetadata());
        $this->assertSame('collapsed', $config->getExportFormat());
        $this->assertSame('/tmp/profiles', $config->getOutputDirectory());
        $this->assertTrue($config->isAsyncExport());
        $this->assertSame(5, $config->getMaxRetries());
        $this->assertSame(2000, $config->getRetryDelay());
    }
    
    /**
     * Test setting and getting the sampling period.
     */
    public function testPeriod(): void
    {
        $config = new ExcimetryConfig();
        
        // Test the default value
        $this->assertSame(0.01, $config->getPeriod());
        
        // Test setting a new value
        $result = $config->setPeriod(0.5);
        
        // Check that the method returns $this for chaining
        $this->assertSame($config, $result);
        
        // Check that the value was set
        $this->assertSame(0.5, $config->getPeriod());
    }
    
    /**
     * Test setting and getting the profiling mode.
     */
    public function testMode(): void
    {
        $config = new ExcimetryConfig();
        
        // Test the default value
        $this->assertSame('wall', $config->getMode());
        
        // Test setting a new value
        $result = $config->setMode('cpu');
        
        // Check that the method returns $this for chaining
        $this->assertSame($config, $result);
        
        // Check that the value was set
        $this->assertSame('cpu', $config->getMode());
        
        // Test setting an invalid value
        $this->expectException(\InvalidArgumentException::class);
        $config->setMode('invalid');
    }
    
    /**
     * Test setting and getting the metadata.
     */
    public function testMetadata(): void
    {
        $config = new ExcimetryConfig();
        
        // Test the default value
        $this->assertSame([], $config->getMetadata());
        
        // Test setting a new value
        $result = $config->setMetadata(['key' => 'value']);
        
        // Check that the method returns $this for chaining
        $this->assertSame($config, $result);
        
        // Check that the value was set
        $this->assertSame(['key' => 'value'], $config->getMetadata());
        
        // Test adding a new metadata item
        $result = $config->addMetadata('key2', 'value2');
        
        // Check that the method returns $this for chaining
        $this->assertSame($config, $result);
        
        // Check that the value was added
        $this->assertSame(['key' => 'value', 'key2' => 'value2'], $config->getMetadata());
    }
    
    /**
     * Test setting and getting the export format.
     */
    public function testExportFormat(): void
    {
        $config = new ExcimetryConfig();
        
        // Test the default value
        $this->assertSame('speedscope', $config->getExportFormat());
        
        // Test setting a new value
        $result = $config->setExportFormat('collapsed');
        
        // Check that the method returns $this for chaining
        $this->assertSame($config, $result);
        
        // Check that the value was set
        $this->assertSame('collapsed', $config->getExportFormat());
        
        // Test setting another valid value
        $config->setExportFormat('otlp');
        $this->assertSame('otlp', $config->getExportFormat());
        
        // Test setting an invalid value
        $this->expectException(\InvalidArgumentException::class);
        $config->setExportFormat('invalid');
    }
    
    /**
     * Test setting and getting the output directory.
     */
    public function testOutputDirectory(): void
    {
        $config = new ExcimetryConfig();
        
        // Test the default value
        $this->assertSame('profiles', $config->getOutputDirectory());
        
        // Test setting a new value
        $result = $config->setOutputDirectory('/tmp/profiles');
        
        // Check that the method returns $this for chaining
        $this->assertSame($config, $result);
        
        // Check that the value was set
        $this->assertSame('/tmp/profiles', $config->getOutputDirectory());
        
        // Test that trailing slashes are removed
        $config->setOutputDirectory('/tmp/profiles/');
        $this->assertSame('/tmp/profiles', $config->getOutputDirectory());
    }
    
    /**
     * Test setting and getting the async export flag.
     */
    public function testAsyncExport(): void
    {
        $config = new ExcimetryConfig();
        
        // Test the default value
        $this->assertFalse($config->isAsyncExport());
        
        // Test setting a new value
        $result = $config->setAsyncExport(true);
        
        // Check that the method returns $this for chaining
        $this->assertSame($config, $result);
        
        // Check that the value was set
        $this->assertTrue($config->isAsyncExport());
    }
    
    /**
     * Test setting and getting the maximum number of retries.
     */
    public function testMaxRetries(): void
    {
        $config = new ExcimetryConfig();
        
        // Test the default value
        $this->assertSame(3, $config->getMaxRetries());
        
        // Test setting a new value
        $result = $config->setMaxRetries(5);
        
        // Check that the method returns $this for chaining
        $this->assertSame($config, $result);
        
        // Check that the value was set
        $this->assertSame(5, $config->getMaxRetries());
        
        // Test setting to 0 (valid)
        $config->setMaxRetries(0);
        $this->assertSame(0, $config->getMaxRetries());
        
        // Test setting a negative value (invalid)
        $this->expectException(\InvalidArgumentException::class);
        $config->setMaxRetries(-1);
    }
    
    /**
     * Test setting and getting the retry delay.
     */
    public function testRetryDelay(): void
    {
        $config = new ExcimetryConfig();
        
        // Test the default value
        $this->assertSame(1000, $config->getRetryDelay());
        
        // Test setting a new value
        $result = $config->setRetryDelay(2000);
        
        // Check that the method returns $this for chaining
        $this->assertSame($config, $result);
        
        // Check that the value was set
        $this->assertSame(2000, $config->getRetryDelay());
        
        // Test setting to 0 (valid)
        $config->setRetryDelay(0);
        $this->assertSame(0, $config->getRetryDelay());
        
        // Test setting a negative value (invalid)
        $this->expectException(\InvalidArgumentException::class);
        $config->setRetryDelay(-1);
    }
    
    /**
     * Test converting the configuration to an array.
     */
    public function testToArray(): void
    {
        $config = new ExcimetryConfig([
            'period' => 0.5,
            'mode' => 'cpu',
            'metadata' => ['key' => 'value'],
            'exportFormat' => 'collapsed',
            'outputDirectory' => '/tmp/profiles',
            'asyncExport' => true,
            'maxRetries' => 5,
            'retryDelay' => 2000,
        ]);
        
        $array = $config->toArray();
        
        $this->assertIsArray($array);
        $this->assertSame(0.5, $array['period']);
        $this->assertSame('cpu', $array['mode']);
        $this->assertSame(['key' => 'value'], $array['metadata']);
        $this->assertSame('collapsed', $array['exportFormat']);
        $this->assertSame('/tmp/profiles', $array['outputDirectory']);
        $this->assertTrue($array['asyncExport']);
        $this->assertSame(5, $array['maxRetries']);
        $this->assertSame(2000, $array['retryDelay']);
    }
    
    /**
     * Test creating a default configuration.
     */
    public function testCreateDefault(): void
    {
        $config = ExcimetryConfig::createDefault();
        
        $this->assertInstanceOf(ExcimetryConfig::class, $config);
        $this->assertSame(0.01, $config->getPeriod());
        $this->assertSame('wall', $config->getMode());
        $this->assertSame([], $config->getMetadata());
        $this->assertSame('speedscope', $config->getExportFormat());
        $this->assertSame('profiles', $config->getOutputDirectory());
        $this->assertFalse($config->isAsyncExport());
        $this->assertSame(3, $config->getMaxRetries());
        $this->assertSame(1000, $config->getRetryDelay());
    }
}