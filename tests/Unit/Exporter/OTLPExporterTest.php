<?php

declare(strict_types=1);

namespace Excimetry\Tests\Unit\Exporter;

use Excimetry\Exporter\OTLPExporter;
use Excimetry\Profiler\ExcimerLog;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the OTLPExporter class.
 */
final class OTLPExporterTest extends TestCase
{
    /**
     * Sample raw log data for testing.
     */
    private const SAMPLE_RAW_LOG = <<<EOT
main;App\Controller\HomeController;index 1
main;App\Service\ReportGenerator;generate;render 2
main;App\Service\ReportGenerator;generate;Database\QueryBuilder;run 3
EOT;

    /**
     * Test exporting with default options.
     */
    public function testExportWithDefaults(): void
    {
        $log = new ExcimerLog(self::SAMPLE_RAW_LOG);
        $exporter = new OTLPExporter();
        
        $result = $exporter->export($log);
        
        // Decode the JSON to check its structure
        $data = json_decode($result, true);
        
        // Check the basic structure
        $this->assertIsArray($data);
        $this->assertArrayHasKey('resourceSpans', $data);
        $this->assertCount(1, $data['resourceSpans']);
        
        $resourceSpan = $data['resourceSpans'][0];
        $this->assertArrayHasKey('resource', $resourceSpan);
        $this->assertArrayHasKey('scopeSpans', $resourceSpan);
        
        // Check the service name
        $resource = $resourceSpan['resource'];
        $this->assertArrayHasKey('attributes', $resource);
        
        $serviceNameAttribute = null;
        foreach ($resource['attributes'] as $attribute) {
            if ($attribute['key'] === 'service.name') {
                $serviceNameAttribute = $attribute;
                break;
            }
        }
        
        $this->assertNotNull($serviceNameAttribute);
        $this->assertArrayHasKey('value', $serviceNameAttribute);
        $this->assertArrayHasKey('stringValue', $serviceNameAttribute['value']);
        $this->assertSame('php-application', $serviceNameAttribute['value']['stringValue']);
        
        // Check the spans
        $scopeSpans = $resourceSpan['scopeSpans'];
        $this->assertCount(1, $scopeSpans);
        
        $scopeSpan = $scopeSpans[0];
        $this->assertArrayHasKey('scope', $scopeSpan);
        $this->assertArrayHasKey('spans', $scopeSpan);
        
        $scope = $scopeSpan['scope'];
        $this->assertArrayHasKey('name', $scope);
        $this->assertArrayHasKey('version', $scope);
        $this->assertSame('excimetry', $scope['name']);
        
        $spans = $scopeSpan['spans'];
        $this->assertCount(3, $spans);
        
        // Check the first span
        $span = $spans[0];
        $this->assertArrayHasKey('name', $span);
        $this->assertArrayHasKey('startTimeUnixNano', $span);
        $this->assertArrayHasKey('endTimeUnixNano', $span);
        $this->assertArrayHasKey('attributes', $span);
        
        // The span name should be the leaf function
        $this->assertSame('index', $span['name']);
        
        // Check the span attributes
        $attributes = $span['attributes'];
        $this->assertCount(2, $attributes);
        
        $stackTraceAttribute = null;
        $sampleCountAttribute = null;
        
        foreach ($attributes as $attribute) {
            if ($attribute['key'] === 'excimetry.stack_trace') {
                $stackTraceAttribute = $attribute;
            } elseif ($attribute['key'] === 'excimetry.sample_count') {
                $sampleCountAttribute = $attribute;
            }
        }
        
        $this->assertNotNull($stackTraceAttribute);
        $this->assertNotNull($sampleCountAttribute);
        
        $this->assertArrayHasKey('value', $stackTraceAttribute);
        $this->assertArrayHasKey('stringValue', $stackTraceAttribute['value']);
        $this->assertSame('main;App\Controller\HomeController;index', $stackTraceAttribute['value']['stringValue']);
        
        $this->assertArrayHasKey('value', $sampleCountAttribute);
        $this->assertArrayHasKey('intValue', $sampleCountAttribute['value']);
        $this->assertSame(1, $sampleCountAttribute['value']['intValue']);
    }
    
    /**
     * Test exporting with a custom service name.
     */
    public function testExportWithCustomServiceName(): void
    {
        $log = new ExcimerLog(self::SAMPLE_RAW_LOG);
        $exporter = new OTLPExporter('custom-service');
        
        $result = $exporter->export($log);
        
        // Decode the JSON to check its structure
        $data = json_decode($result, true);
        
        // Check the service name
        $resource = $data['resourceSpans'][0]['resource'];
        $serviceNameAttribute = null;
        
        foreach ($resource['attributes'] as $attribute) {
            if ($attribute['key'] === 'service.name') {
                $serviceNameAttribute = $attribute;
                break;
            }
        }
        
        $this->assertNotNull($serviceNameAttribute);
        $this->assertSame('custom-service', $serviceNameAttribute['value']['stringValue']);
    }
    
    /**
     * Test setting and getting the service name.
     */
    public function testSetAndGetServiceName(): void
    {
        $exporter = new OTLPExporter();
        $this->assertSame('php-application', $exporter->getServiceName());
        
        $exporter->setServiceName('new-service');
        $this->assertSame('new-service', $exporter->getServiceName());
    }
    
    /**
     * Test setting and getting the format.
     */
    public function testSetAndGetFormat(): void
    {
        $exporter = new OTLPExporter();
        $this->assertSame('json', $exporter->getFormat());
        
        $exporter->setFormat('protobuf');
        $this->assertSame('protobuf', $exporter->getFormat());
    }
    
    /**
     * Test setting an invalid format.
     */
    public function testSetInvalidFormat(): void
    {
        $exporter = new OTLPExporter();
        
        $this->expectException(\InvalidArgumentException::class);
        $exporter->setFormat('invalid');
    }
    
    /**
     * Test creating an exporter with an invalid format.
     */
    public function testConstructWithInvalidFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new OTLPExporter('service', 'invalid');
    }
    
    /**
     * Test getting the content type for JSON format.
     */
    public function testGetContentTypeForJson(): void
    {
        $exporter = new OTLPExporter(format: 'json');
        $this->assertSame('application/json', $exporter->getContentType());
    }
    
    /**
     * Test getting the content type for Protobuf format.
     */
    public function testGetContentTypeForProtobuf(): void
    {
        $exporter = new OTLPExporter(format: 'protobuf');
        $this->assertSame('application/x-protobuf', $exporter->getContentType());
    }
    
    /**
     * Test getting the file extension for JSON format.
     */
    public function testGetFileExtensionForJson(): void
    {
        $exporter = new OTLPExporter(format: 'json');
        $this->assertSame('json', $exporter->getFileExtension());
    }
    
    /**
     * Test getting the file extension for Protobuf format.
     */
    public function testGetFileExtensionForProtobuf(): void
    {
        $exporter = new OTLPExporter(format: 'protobuf');
        $this->assertSame('bin', $exporter->getFileExtension());
    }
    
    /**
     * Test exporting an empty log.
     */
    public function testExportEmptyLog(): void
    {
        $log = new ExcimerLog('');
        $exporter = new OTLPExporter();
        
        $result = $exporter->export($log);
        
        // Decode the JSON to check its structure
        $data = json_decode($result, true);
        
        // Check the basic structure
        $this->assertIsArray($data);
        $this->assertArrayHasKey('resourceSpans', $data);
        
        // Check that there are no spans
        $spans = $data['resourceSpans'][0]['scopeSpans'][0]['spans'];
        $this->assertCount(0, $spans);
    }
    
    /**
     * Test that metadata is included as resource attributes.
     */
    public function testMetadataIncludedAsResourceAttributes(): void
    {
        $metadata = [
            'foo' => 'bar',
            'baz' => 123,
            'qux' => true,
        ];
        
        $log = new ExcimerLog(self::SAMPLE_RAW_LOG, $metadata);
        $exporter = new OTLPExporter();
        
        $result = $exporter->export($log);
        
        // Decode the JSON to check its structure
        $data = json_decode($result, true);
        
        // Check the resource attributes
        $attributes = $data['resourceSpans'][0]['resource']['attributes'];
        
        $metadataAttributes = [];
        foreach ($attributes as $attribute) {
            if (strpos($attribute['key'], 'excimetry.') === 0) {
                $metadataAttributes[$attribute['key']] = $attribute['value']['stringValue'];
            }
        }
        
        $this->assertArrayHasKey('excimetry.foo', $metadataAttributes);
        $this->assertArrayHasKey('excimetry.baz', $metadataAttributes);
        $this->assertArrayHasKey('excimetry.qux', $metadataAttributes);
        
        $this->assertSame('bar', $metadataAttributes['excimetry.foo']);
        $this->assertSame('123', $metadataAttributes['excimetry.baz']);
        $this->assertSame('1', $metadataAttributes['excimetry.qux']);
    }
}