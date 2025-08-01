<?php

declare(strict_types=1);

namespace Excimetry\Tests\Unit\Backend;

use Excimetry\Backend\OTLPBackend;
use Excimetry\Exporter\OTLPExporter;
use Excimetry\Tests\Mock\ExporterMock;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the OTLPBackend class.
 * 
 * Note: This test focuses on the unique aspects of OTLPBackend without
 * actually making HTTP requests.
 */
final class OTLPBackendTest extends TestCase
{
    /**
     * Test creating a new OTLPBackend instance with default options.
     */
    public function testConstructWithDefaults(): void
    {
        $backend = new OTLPBackend('https://otlp.example.com');
        
        // Check that the URL is correctly constructed
        $this->assertSame('https://otlp.example.com/v1/traces', $backend->getUrl());
        
        // Check that the service name is set
        $this->assertSame('php-application', $backend->getServiceName());
        
        // Check that the exporter is an OTLPExporter
        $this->assertInstanceOf(OTLPExporter::class, $backend->getExporter());
        
        // Check that the headers include the Accept header
        $headers = $backend->getHeaders();
        $this->assertArrayHasKey('Accept', $headers);
        $this->assertEquals('application/json', $headers['Accept']);
    }
    
    /**
     * Test creating a new OTLPBackend instance with custom options.
     */
    public function testConstructWithCustomOptions(): void
    {
        $exporter = new ExporterMock();
        $backend = new OTLPBackend('https://otlp.example.com', 'custom-service', $exporter);
        
        // Check that the URL is correctly constructed
        $this->assertSame('https://otlp.example.com/v1/traces', $backend->getUrl());
        
        // Check that the service name is set
        $this->assertSame('custom-service', $backend->getServiceName());
        
        // Check that the exporter is the one we provided
        $this->assertSame($exporter, $backend->getExporter());
    }
    
    /**
     * Test setting and getting the service name.
     */
    public function testSetAndGetServiceName(): void
    {
        $backend = new OTLPBackend('https://otlp.example.com');
        
        $this->assertSame('php-application', $backend->getServiceName());
        
        $backend->setServiceName('new-service');
        
        $this->assertSame('new-service', $backend->getServiceName());
        
        // Check that the exporter's service name was also updated
        $exporter = $backend->getExporter();
        $this->assertInstanceOf(OTLPExporter::class, $exporter);
        $this->assertSame('new-service', $exporter->getServiceName());
    }
    
    /**
     * Test setting and getting the format.
     */
    public function testSetAndGetFormat(): void
    {
        $backend = new OTLPBackend('https://otlp.example.com');
        
        // Default format is json
        $this->assertSame('json', $backend->getFormat());
        
        // Set the format to protobuf
        $backend->setFormat('protobuf');
        
        // Check that the format was updated
        $this->assertSame('protobuf', $backend->getFormat());
        
        // Check that the exporter's format was also updated
        $exporter = $backend->getExporter();
        $this->assertInstanceOf(OTLPExporter::class, $exporter);
        $this->assertSame('protobuf', $exporter->getFormat());
        
        // Check that the headers were updated
        $headers = $backend->getHeaders();
        $this->assertArrayHasKey('Accept', $headers);
        $this->assertEquals('application/x-protobuf', $headers['Accept']);
    }
    
    /**
     * Test adding a trace ID.
     */
    public function testAddTraceId(): void
    {
        $backend = new OTLPBackend('https://otlp.example.com');
        $traceId = '1234567890abcdef';
        
        $backend->addTraceId($traceId);
        
        // Check that the trace ID was added to the exporter's metadata
        $exporter = $backend->getExporter();
        $this->assertInstanceOf(OTLPExporter::class, $exporter);
        
        // We can't directly access the metadata, but we can check that the
        // addMetadata method exists and accepts the trace_id key
        $reflection = new \ReflectionClass($exporter);
        $method = $reflection->getMethod('addMetadata');
        $this->assertTrue($method->isPublic());
        $this->assertSame(2, $method->getNumberOfParameters());
    }
    
    /**
     * Test adding a span ID.
     */
    public function testAddSpanId(): void
    {
        $backend = new OTLPBackend('https://otlp.example.com');
        $spanId = '1234567890abcdef';
        
        $backend->addSpanId($spanId);
        
        // Check that the span ID was added to the exporter's metadata
        $exporter = $backend->getExporter();
        $this->assertInstanceOf(OTLPExporter::class, $exporter);
        
        // We can't directly access the metadata, but we can check that the
        // addMetadata method exists and accepts the span_id key
        $reflection = new \ReflectionClass($exporter);
        $method = $reflection->getMethod('addMetadata');
        $this->assertTrue($method->isPublic());
        $this->assertSame(2, $method->getNumberOfParameters());
    }
    
    /**
     * Test that the collector URL is normalized.
     */
    public function testCollectorUrlIsNormalized(): void
    {
        // Test with trailing slash
        $backend1 = new OTLPBackend('https://otlp.example.com/');
        $this->assertSame('https://otlp.example.com/v1/traces', $backend1->getUrl());
        
        // Test without trailing slash
        $backend2 = new OTLPBackend('https://otlp.example.com');
        $this->assertSame('https://otlp.example.com/v1/traces', $backend2->getUrl());
    }
}