<?php

declare(strict_types=1);

namespace Excimetry\Tests\Unit\Exporter;

use Excimetry\Exporter\CollapsedExporter;
use Excimetry\Profiler\ExcimerLog;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the CollapsedExporter class.
 */
final class CollapsedExporterTest extends TestCase
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
        $exporter = new CollapsedExporter();
        
        $result = $exporter->export($log);
        
        $expected = <<<EOT
main;App\Controller\HomeController;index 1
main;App\Service\ReportGenerator;generate;render 2
main;App\Service\ReportGenerator;generate;Database\QueryBuilder;run 3

EOT;
        
        $this->assertSame($expected, $result);
    }
    
    /**
     * Test exporting with reversed stack traces.
     */
    public function testExportWithReverseStack(): void
    {
        $log = new ExcimerLog(self::SAMPLE_RAW_LOG);
        $exporter = new CollapsedExporter(true);
        
        $result = $exporter->export($log);
        
        $expected = <<<EOT
index;App\Controller\HomeController;main 1
render;generate;App\Service\ReportGenerator;main 2
run;Database\QueryBuilder;generate;App\Service\ReportGenerator;main 3

EOT;
        
        $this->assertSame($expected, $result);
    }
    
    /**
     * Test exporting with a custom delimiter.
     */
    public function testExportWithCustomDelimiter(): void
    {
        $log = new ExcimerLog(self::SAMPLE_RAW_LOG);
        $exporter = new CollapsedExporter(false, ',');
        
        $result = $exporter->export($log);
        
        $expected = <<<EOT
main,App\Controller\HomeController,index 1
main,App\Service\ReportGenerator,generate,render 2
main,App\Service\ReportGenerator,generate,Database\QueryBuilder,run 3

EOT;
        
        $this->assertSame($expected, $result);
    }
    
    /**
     * Test exporting with both reversed stack traces and a custom delimiter.
     */
    public function testExportWithReverseStackAndCustomDelimiter(): void
    {
        $log = new ExcimerLog(self::SAMPLE_RAW_LOG);
        $exporter = new CollapsedExporter(true, ',');
        
        $result = $exporter->export($log);
        
        $expected = <<<EOT
index,App\Controller\HomeController,main 1
render,generate,App\Service\ReportGenerator,main 2
run,Database\QueryBuilder,generate,App\Service\ReportGenerator,main 3

EOT;
        
        $this->assertSame($expected, $result);
    }
    
    /**
     * Test exporting an empty log.
     */
    public function testExportEmptyLog(): void
    {
        $log = new ExcimerLog('');
        $exporter = new CollapsedExporter();
        
        $result = $exporter->export($log);
        
        $this->assertSame('', $result);
    }
    
    /**
     * Test getting the content type.
     */
    public function testGetContentType(): void
    {
        $exporter = new CollapsedExporter();
        $this->assertSame('text/plain', $exporter->getContentType());
    }
    
    /**
     * Test getting the file extension.
     */
    public function testGetFileExtension(): void
    {
        $exporter = new CollapsedExporter();
        $this->assertSame('txt', $exporter->getFileExtension());
    }
    
    /**
     * Test exporting with duplicate stack traces.
     */
    public function testExportWithDuplicateStackTraces(): void
    {
        $rawLog = <<<EOT
main;App\Controller\HomeController;index 1
main;App\Controller\HomeController;index 2
main;App\Service\ReportGenerator;generate;render 3
EOT;
        
        $log = new ExcimerLog($rawLog);
        $exporter = new CollapsedExporter();
        
        $result = $exporter->export($log);
        
        $expected = <<<EOT
main;App\Controller\HomeController;index 3
main;App\Service\ReportGenerator;generate;render 3

EOT;
        
        $this->assertSame($expected, $result);
    }
}