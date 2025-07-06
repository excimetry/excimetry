<?php

declare(strict_types=1);

namespace Excimetry\Tests\Unit\OpenTelemetry;

use Excimetry\OpenTelemetry\OpenTelemetryIntegration;
use Excimetry\Profiler\ExcimerProfiler;
use Excimetry\Backend\OTLPBackend;
use Excimetry\Exporter\OTLPExporter;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Unit tests for the OpenTelemetryIntegration class.
 * 
 * Note: Since ExcimerProfiler, OTLPBackend, and ExcimerLog are final classes,
 * we can't mock them directly. Instead, we focus on testing the static create
 * method and verifying the class structure.
 */
final class OpenTelemetryIntegrationTest extends TestCase
{
    /**
     * Test the static create method.
     */
    public function testCreate(): void
    {
        // We can't test the create method directly because it creates real objects
        // that depend on the ext-excimer extension, but we can check that the method
        // exists and returns an OpenTelemetryIntegration instance
        $reflection = new ReflectionClass(OpenTelemetryIntegration::class);
        $method = $reflection->getMethod('create');

        $this->assertTrue($method->isStatic());
        $this->assertSame(OpenTelemetryIntegration::class, $method->getReturnType()->getName());
    }

    /**
     * Test the class structure.
     */
    public function testClassStructure(): void
    {
        $reflection = new ReflectionClass(OpenTelemetryIntegration::class);

        // Check that the class has the expected properties
        $this->assertTrue($reflection->hasProperty('profiler'));
        $this->assertTrue($reflection->hasProperty('backend'));

        // Check that the class has the expected methods
        $this->assertTrue($reflection->hasMethod('start'));
        $this->assertTrue($reflection->hasMethod('stop'));
        $this->assertTrue($reflection->hasMethod('reset'));
        $this->assertTrue($reflection->hasMethod('addTraceId'));
        $this->assertTrue($reflection->hasMethod('addSpanId'));
        $this->assertTrue($reflection->hasMethod('addMetadata'));
        $this->assertTrue($reflection->hasMethod('getProfiler'));
        $this->assertTrue($reflection->hasMethod('getBackend'));

        // Check the return types of the methods
        $this->assertSame(OpenTelemetryIntegration::class, $reflection->getMethod('start')->getReturnType()->getName());
        $this->assertSame(OpenTelemetryIntegration::class, $reflection->getMethod('stop')->getReturnType()->getName());
        $this->assertSame(OpenTelemetryIntegration::class, $reflection->getMethod('reset')->getReturnType()->getName());
        $this->assertSame(OpenTelemetryIntegration::class, $reflection->getMethod('addTraceId')->getReturnType()->getName());
        $this->assertSame(OpenTelemetryIntegration::class, $reflection->getMethod('addSpanId')->getReturnType()->getName());
        $this->assertSame(OpenTelemetryIntegration::class, $reflection->getMethod('addMetadata')->getReturnType()->getName());
        $this->assertSame(ExcimerProfiler::class, $reflection->getMethod('getProfiler')->getReturnType()->getName());
        $this->assertSame(OTLPBackend::class, $reflection->getMethod('getBackend')->getReturnType()->getName());
    }

    /**
     * Test the code for the start method.
     */
    public function testStartMethodCode(): void
    {
        $reflection = new ReflectionClass(OpenTelemetryIntegration::class);
        $method = $reflection->getMethod('start');

        // Get the source code of the method
        $fileName = $reflection->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        $lines = file($fileName);
        $methodCode = implode('', array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        // Check that the method calls the profiler's start method
        $this->assertStringContainsString('$this->profiler->start()', $methodCode);
    }

    /**
     * Test the code for the stop method.
     */
    public function testStopMethodCode(): void
    {
        $reflection = new ReflectionClass(OpenTelemetryIntegration::class);
        $method = $reflection->getMethod('stop');

        // Get the source code of the method
        $fileName = $reflection->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        $lines = file($fileName);
        $methodCode = implode('', array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        // Check that the method calls the profiler's stop method and the backend's send method
        $this->assertStringContainsString('$this->profiler->stop()', $methodCode);
        $this->assertStringContainsString('$log = $this->profiler->getLog()', $methodCode);
        $this->assertStringContainsString('$this->backend->send($log)', $methodCode);
    }

    /**
     * Test the code for the addTraceId method.
     */
    public function testAddTraceIdMethodCode(): void
    {
        $reflection = new ReflectionClass(OpenTelemetryIntegration::class);
        $method = $reflection->getMethod('addTraceId');

        // Get the source code of the method
        $fileName = $reflection->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        $lines = file($fileName);
        $methodCode = implode('', array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        // Check that the method calls the backend's addTraceId method and the profiler's addMetadata method
        $this->assertStringContainsString('$this->backend->addTraceId($traceId)', $methodCode);
        $this->assertStringContainsString('$this->profiler->addMetadata(\'trace_id\', $traceId)', $methodCode);
    }
}
