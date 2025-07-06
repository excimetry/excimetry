<?php

declare(strict_types=1);

namespace Excimetry\Tests\Integration;

use Excimetry\Excimetry;
use Excimetry\Exporter\CollapsedExporter;
use Excimetry\Exporter\SpeedscopeExporter;
use Excimetry\Backend\FileBackend;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for the Excimetry library.
 * 
 * This test demonstrates how to use the Excimetry library in a real application.
 * It profiles a PHP script and exports the results to different formats.
 * 
 * Note: This test requires the ext-excimer extension to be installed.
 */
final class ExcimetryIntegrationTest extends TestCase
{
    /**
     * @var string The temporary directory for test outputs
     */
    private string $tempDir;
    
    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Skip the test if the ext-excimer extension is not installed
        if (!extension_loaded('excimer')) {
            $this->markTestSkipped('The ext-excimer extension is not installed.');
        }
        
        // Create a temporary directory for test outputs
        $this->tempDir = sys_get_temp_dir() . '/excimetry_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
    }
    
    /**
     * Clean up the test environment.
     */
    protected function tearDown(): void
    {
        // Remove the temporary directory and its contents
        if (isset($this->tempDir) && is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
        
        parent::tearDown();
    }
    
    /**
     * Test profiling a PHP script and exporting the results to collapsed format.
     */
    public function testProfileAndExportToCollapsedFormat(): void
    {
        // Create a new Excimetry instance
        $excimetry = new Excimetry();
        
        // Start profiling
        $excimetry->start();
        
        // Run some code to profile
        $this->runSampleCode();
        
        // Stop profiling
        $excimetry->stop();
        
        // Export the profiling data to collapsed format
        $exporter = new CollapsedExporter();
        $result = $excimetry->export($exporter);
        
        // Check that the result is not empty
        $this->assertNotEmpty($result);
        
        // Check that the result is a string
        $this->assertIsString($result);
        
        // Check that the result contains stack traces
        $this->assertStringContainsString(';', $result);
    }
    
    /**
     * Test profiling a PHP script and exporting the results to speedscope format.
     */
    public function testProfileAndExportToSpeedscopeFormat(): void
    {
        // Create a new Excimetry instance
        $excimetry = new Excimetry();
        
        // Start profiling
        $excimetry->start();
        
        // Run some code to profile
        $this->runSampleCode();
        
        // Stop profiling
        $excimetry->stop();
        
        // Export the profiling data to speedscope format
        $exporter = new SpeedscopeExporter('Integration Test');
        $result = $excimetry->export($exporter);
        
        // Check that the result is not empty
        $this->assertNotEmpty($result);
        
        // Check that the result is a string
        $this->assertIsString($result);
        
        // Check that the result is valid JSON
        $data = json_decode($result, true);
        $this->assertIsArray($data);
        
        // Check that the JSON contains the expected structure
        $this->assertArrayHasKey('version', $data);
        $this->assertArrayHasKey('shared', $data);
        $this->assertArrayHasKey('profiles', $data);
        
        // Check that the profile name is set correctly
        $this->assertSame('Integration Test', $data['profiles'][0]['name']);
    }
    
    /**
     * Test profiling a PHP script and saving the results to a file.
     */
    public function testProfileAndSaveToFile(): void
    {
        // Create a new Excimetry instance
        $excimetry = new Excimetry();
        
        // Start profiling
        $excimetry->start();
        
        // Run some code to profile
        $this->runSampleCode();
        
        // Stop profiling
        $excimetry->stop();
        
        // Export the profiling data and save it to a file
        $exporter = new SpeedscopeExporter('Integration Test');
        $backend = new FileBackend($exporter, $this->tempDir, 'profile.json');
        
        $result = $backend->send($excimetry->getLog());
        
        // Check that the file was created successfully
        $this->assertTrue($result);
        
        // Check that the file exists
        $filePath = $this->tempDir . '/profile.json';
        $this->assertFileExists($filePath);
        
        // Check that the file is not empty
        $this->assertGreaterThan(0, filesize($filePath));
        
        // Check that the file contains valid JSON
        $content = file_get_contents($filePath);
        $data = json_decode($content, true);
        $this->assertIsArray($data);
    }
    
    /**
     * Run some sample code to profile.
     */
    private function runSampleCode(): void
    {
        // Perform some CPU-intensive operations
        for ($i = 0; $i < 1000; $i++) {
            $array = range(0, 100);
            array_map('sqrt', $array);
        }
        
        // Perform some memory-intensive operations
        $data = [];
        for ($i = 0; $i < 1000; $i++) {
            $data[] = str_repeat('x', 100);
        }
        
        // Perform some I/O operations
        $tempFile = $this->tempDir . '/test.txt';
        file_put_contents($tempFile, str_repeat('x', 10000));
        $content = file_get_contents($tempFile);
        unlink($tempFile);
    }
    
    /**
     * Recursively remove a directory and its contents.
     * 
     * @param string $dir The directory to remove
     * @return void
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }
}