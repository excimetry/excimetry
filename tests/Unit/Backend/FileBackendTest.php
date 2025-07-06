<?php

declare(strict_types=1);

namespace Excimetry\Tests\Unit\Backend;

use Excimetry\Backend\FileBackend;
use Excimetry\Profiler\ExcimerLog;
use Excimetry\Tests\Mock\ExporterMock;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the FileBackend class.
 */
final class FileBackendTest extends TestCase
{
    /**
     * @var string The temporary directory for testing
     */
    private string $tempDir;
    
    /**
     * @var ExporterMock The mock exporter
     */
    private ExporterMock $exporter;
    
    /**
     * @var FileBackend The FileBackend instance under test
     */
    private FileBackend $backend;
    
    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a temporary directory for testing
        $this->tempDir = sys_get_temp_dir() . '/excimetry_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        
        // Create a mock exporter
        $this->exporter = new ExporterMock();
        $this->exporter->setExportData('test data');
        $this->exporter->setFileExtension('txt');
        
        // Create the FileBackend instance
        $this->backend = new FileBackend($this->exporter, $this->tempDir);
    }
    
    /**
     * Clean up the test environment.
     */
    protected function tearDown(): void
    {
        // Remove the temporary directory and its contents
        $this->removeDirectory($this->tempDir);
        
        parent::tearDown();
    }
    
    /**
     * Test sending profile data with a generated filename.
     */
    public function testSendWithGeneratedFilename(): void
    {
        $log = new ExcimerLog('test log');
        
        $result = $this->backend->send($log);
        
        $this->assertTrue($result);
        $this->assertTrue($this->exporter->wasCalled('export'));
        $this->assertTrue($this->exporter->wasCalled('getFileExtension'));
        
        // Check that a file was created in the temporary directory
        $files = glob($this->tempDir . '/*.txt');
        $this->assertCount(1, $files);
        
        // Check the file contents
        $fileContents = file_get_contents($files[0]);
        $this->assertSame('test data', $fileContents);
    }
    
    /**
     * Test sending profile data with a specified filename.
     */
    public function testSendWithSpecifiedFilename(): void
    {
        $log = new ExcimerLog('test log');
        $filename = 'test_profile.txt';
        
        $this->backend->setFilename($filename);
        $result = $this->backend->send($log);
        
        $this->assertTrue($result);
        $this->assertTrue($this->exporter->wasCalled('export'));
        
        // Check that the specified file was created
        $filePath = $this->tempDir . '/' . $filename;
        $this->assertFileExists($filePath);
        
        // Check the file contents
        $fileContents = file_get_contents($filePath);
        $this->assertSame('test data', $fileContents);
    }
    
    /**
     * Test sending profile data with a non-existent directory.
     */
    public function testSendWithNonExistentDirectory(): void
    {
        $log = new ExcimerLog('test log');
        $nonExistentDir = $this->tempDir . '/non_existent';
        
        $this->backend->setDirectory($nonExistentDir);
        $result = $this->backend->send($log);
        
        $this->assertTrue($result);
        $this->assertTrue($this->exporter->wasCalled('export'));
        
        // Check that the directory was created
        $this->assertDirectoryExists($nonExistentDir);
        
        // Check that a file was created in the directory
        $files = glob($nonExistentDir . '/*.txt');
        $this->assertCount(1, $files);
    }
    
    /**
     * Test setting and getting the directory.
     */
    public function testSetAndGetDirectory(): void
    {
        $this->assertSame($this->tempDir, $this->backend->getDirectory());
        
        $newDir = $this->tempDir . '/new';
        $this->backend->setDirectory($newDir);
        
        $this->assertSame($newDir, $this->backend->getDirectory());
    }
    
    /**
     * Test setting and getting the filename.
     */
    public function testSetAndGetFilename(): void
    {
        $this->assertNull($this->backend->getFilename());
        
        $filename = 'test.txt';
        $this->backend->setFilename($filename);
        
        $this->assertSame($filename, $this->backend->getFilename());
    }
    
    /**
     * Test that the directory path is normalized.
     */
    public function testDirectoryPathIsNormalized(): void
    {
        $dirWithTrailingSlash = $this->tempDir . '/';
        $this->backend->setDirectory($dirWithTrailingSlash);
        
        $this->assertSame($this->tempDir, $this->backend->getDirectory());
    }
    
    /**
     * Test that the exporter's file extension is used for the generated filename.
     */
    public function testExporterFileExtensionIsUsed(): void
    {
        $this->exporter->setFileExtension('json');
        
        $log = new ExcimerLog('test log');
        $this->backend->send($log);
        
        // Check that a file with the correct extension was created
        $files = glob($this->tempDir . '/*.json');
        $this->assertCount(1, $files);
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