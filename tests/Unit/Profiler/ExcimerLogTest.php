<?php

declare(strict_types=1);

namespace Excimetry\Tests\Unit\Profiler;

use Excimetry\Profiler\ExcimerLog;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the ExcimerLog class.
 */
final class ExcimerLogTest extends TestCase
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
     * Test creating a new ExcimerLog instance.
     */
    public function testConstruct(): void
    {
        $metadata = ['foo' => 'bar'];
        $log = new ExcimerLog(self::SAMPLE_RAW_LOG, $metadata);
        
        $this->assertSame(self::SAMPLE_RAW_LOG, $log->getRawLog());
        $this->assertSame($metadata, $log->getMetadata());
    }
    
    /**
     * Test getting the raw log data.
     */
    public function testGetRawLog(): void
    {
        $log = new ExcimerLog(self::SAMPLE_RAW_LOG);
        $this->assertSame(self::SAMPLE_RAW_LOG, $log->getRawLog());
    }
    
    /**
     * Test getting the metadata.
     */
    public function testGetMetadata(): void
    {
        $metadata = ['foo' => 'bar', 'baz' => 'qux'];
        $log = new ExcimerLog(self::SAMPLE_RAW_LOG, $metadata);
        $this->assertSame($metadata, $log->getMetadata());
    }
    
    /**
     * Test adding metadata.
     */
    public function testAddMetadata(): void
    {
        $log = new ExcimerLog(self::SAMPLE_RAW_LOG, ['foo' => 'bar']);
        $log->addMetadata('baz', 'qux');
        
        $expected = ['foo' => 'bar', 'baz' => 'qux'];
        $this->assertSame($expected, $log->getMetadata());
    }
    
    /**
     * Test getting the parsed log data.
     */
    public function testGetParsedLog(): void
    {
        $log = new ExcimerLog(self::SAMPLE_RAW_LOG);
        $parsedLog = $log->getParsedLog();
        
        $this->assertIsArray($parsedLog);
        $this->assertCount(3, $parsedLog);
        
        // Check the first entry
        $this->assertArrayHasKey('stackTrace', $parsedLog[0]);
        $this->assertArrayHasKey('frames', $parsedLog[0]);
        $this->assertArrayHasKey('count', $parsedLog[0]);
        
        $this->assertSame('main;App\Controller\HomeController;index', $parsedLog[0]['stackTrace']);
        $this->assertSame(['main', 'App\Controller\HomeController', 'index'], $parsedLog[0]['frames']);
        $this->assertSame(1, $parsedLog[0]['count']);
        
        // Check the second entry
        $this->assertSame('main;App\Service\ReportGenerator;generate;render', $parsedLog[1]['stackTrace']);
        $this->assertSame(['main', 'App\Service\ReportGenerator', 'generate', 'render'], $parsedLog[1]['frames']);
        $this->assertSame(2, $parsedLog[1]['count']);
        
        // Check the third entry
        $this->assertSame('main;App\Service\ReportGenerator;generate;Database\QueryBuilder;run', $parsedLog[2]['stackTrace']);
        $this->assertSame(['main', 'App\Service\ReportGenerator', 'generate', 'Database\QueryBuilder', 'run'], $parsedLog[2]['frames']);
        $this->assertSame(3, $parsedLog[2]['count']);
    }
    
    /**
     * Test formatting the log data in collapsed format.
     */
    public function testFormatCollapsed(): void
    {
        $log = new ExcimerLog(self::SAMPLE_RAW_LOG);
        $collapsed = $log->formatCollapsed();
        
        $expected = <<<EOT
main;App\Controller\HomeController;index 1
main;App\Service\ReportGenerator;generate;render 2
main;App\Service\ReportGenerator;generate;Database\QueryBuilder;run 3

EOT;
        
        $this->assertSame($expected, $collapsed);
    }
    
    /**
     * Test getting the log data in speedscope format.
     */
    public function testGetSpeedscopeData(): void
    {
        $log = new ExcimerLog(self::SAMPLE_RAW_LOG, ['test' => 'metadata']);
        $speedscope = $log->getSpeedscopeData();
        
        // Check the basic structure
        $this->assertArrayHasKey('version', $speedscope);
        $this->assertArrayHasKey('shared', $speedscope);
        $this->assertArrayHasKey('profiles', $speedscope);
        $this->assertArrayHasKey('activeProfileIndex', $speedscope);
        $this->assertArrayHasKey('exporter', $speedscope);
        $this->assertArrayHasKey('metadata', $speedscope);
        
        // Check the metadata
        $this->assertSame(['test' => 'metadata'], $speedscope['metadata']);
        
        // Check the frames
        $this->assertArrayHasKey('frames', $speedscope['shared']);
        $frames = $speedscope['shared']['frames'];
        $this->assertIsArray($frames);
        
        // We should have unique frames for each function in the stack traces
        $uniqueFrames = [
            'main',
            'App\Controller\HomeController',
            'index',
            'App\Service\ReportGenerator',
            'generate',
            'render',
            'Database\QueryBuilder',
            'run',
        ];
        
        // Check that all unique frames are present
        $frameNames = array_column($frames, 'name');
        foreach ($uniqueFrames as $frame) {
            $this->assertContains($frame, $frameNames);
        }
        
        // Check the profiles
        $this->assertIsArray($speedscope['profiles']);
        $this->assertCount(1, $speedscope['profiles']);
        
        $profile = $speedscope['profiles'][0];
        $this->assertArrayHasKey('type', $profile);
        $this->assertArrayHasKey('name', $profile);
        $this->assertArrayHasKey('unit', $profile);
        $this->assertArrayHasKey('startValue', $profile);
        $this->assertArrayHasKey('endValue', $profile);
        $this->assertArrayHasKey('events', $profile);
        
        $this->assertSame('evented', $profile['type']);
        $this->assertSame('Excimer Profile', $profile['name']);
        $this->assertSame('samples', $profile['unit']);
        $this->assertSame(0, $profile['startValue']);
        $this->assertSame(6, $profile['endValue']); // Sum of all counts (1 + 2 + 3)
        
        // Check the events
        $this->assertIsArray($profile['events']);
        $this->assertNotEmpty($profile['events']);
    }
    
    /**
     * Test parsing an empty log.
     */
    public function testParseEmptyLog(): void
    {
        $log = new ExcimerLog('');
        $parsedLog = $log->getParsedLog();
        
        $this->assertIsArray($parsedLog);
        $this->assertEmpty($parsedLog);
    }
    
    /**
     * Test parsing a log with invalid format.
     */
    public function testParseInvalidLog(): void
    {
        $log = new ExcimerLog("invalid log format\nwithout counts");
        $parsedLog = $log->getParsedLog();
        
        $this->assertIsArray($parsedLog);
        $this->assertEmpty($parsedLog);
    }
}