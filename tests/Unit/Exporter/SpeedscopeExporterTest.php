<?php

declare(strict_types=1);

namespace Excimetry\Tests\Unit\Exporter;

use Excimetry\Exporter\SpeedscopeExporter;
use Excimetry\Profiler\ExcimerLog;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the SpeedscopeExporter class.
 */
final class SpeedscopeExporterTest extends TestCase
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
        $exporter = new SpeedscopeExporter();

        $result = $exporter->export($log);

        // Decode the JSON to check its structure
        $data = json_decode($result, true);

        // Check the basic structure
        $this->assertIsArray($data);
        $this->assertArrayHasKey('version', $data);
        $this->assertArrayHasKey('shared', $data);
        $this->assertArrayHasKey('profiles', $data);
        $this->assertArrayHasKey('activeProfileIndex', $data);
        $this->assertArrayHasKey('exporter', $data);

        // Check the profile name
        $this->assertArrayHasKey('name', $data['profiles'][0]);
        $this->assertSame('Excimer Profile', $data['profiles'][0]['name']);
    }

    /**
     * Test exporting with a custom profile name.
     */
    public function testExportWithCustomProfileName(): void
    {
        $log = new ExcimerLog(self::SAMPLE_RAW_LOG);
        $exporter = new SpeedscopeExporter('Custom Profile Name');

        $result = $exporter->export($log);

        // Decode the JSON to check its structure
        $data = json_decode($result, true);

        // Check the profile name
        $this->assertArrayHasKey('name', $data['profiles'][0]);
        $this->assertSame('Custom Profile Name', $data['profiles'][0]['name']);
    }

    /**
     * Test setting and getting the profile name.
     */
    public function testSetAndGetProfileName(): void
    {
        $exporter = new SpeedscopeExporter();
        $this->assertSame('Excimer Profile', $exporter->getProfileName());

        $exporter->setProfileName('New Profile Name');
        $this->assertSame('New Profile Name', $exporter->getProfileName());
    }

    /**
     * Test getting the content type.
     */
    public function testGetContentType(): void
    {
        $exporter = new SpeedscopeExporter();
        $this->assertSame('application/json', $exporter->getContentType());
    }

    /**
     * Test getting the file extension.
     */
    public function testGetFileExtension(): void
    {
        $exporter = new SpeedscopeExporter();
        $this->assertSame('json', $exporter->getFileExtension());
    }

    /**
     * Test exporting an empty log.
     */
    public function testExportEmptyLog(): void
    {
        $log = new ExcimerLog('');
        $exporter = new SpeedscopeExporter();

        $result = $exporter->export($log);

        // Decode the JSON to check its structure
        $data = json_decode($result, true);

        // Check the basic structure
        $this->assertIsArray($data);
        $this->assertArrayHasKey('version', $data);
        $this->assertArrayHasKey('shared', $data);
        $this->assertArrayHasKey('profiles', $data);

        // Check that the frames array is empty
        $this->assertEmpty($data['shared']['frames']);

        // Check that the events array is empty
        $this->assertEmpty($data['profiles'][0]['events']);
    }

    /**
     * Test that the exported JSON is valid.
     */
    public function testExportedJsonIsValid(): void
    {
        $log = new ExcimerLog(self::SAMPLE_RAW_LOG);
        $exporter = new SpeedscopeExporter();

        $result = $exporter->export($log);

        // Decode the JSON to check that it's valid
        $data = json_decode($result, true);

        $this->assertNotNull($data);
        $this->assertIsArray($data);
    }

    /**
     * Test that the exported JSON includes metadata.
     */
    public function testExportedJsonIncludesMetadata(): void
    {
        $metadata = ['foo' => 'bar', 'baz' => 'qux'];
        $log = new ExcimerLog(self::SAMPLE_RAW_LOG, $metadata);
        $exporter = new SpeedscopeExporter();

        $result = $exporter->export($log);

        // Decode the JSON to check that it includes the metadata
        $data = json_decode($result, true);

        $this->assertArrayHasKey('metadata', $data);
        $this->assertSame($metadata, $data['metadata']);
    }

    /**
     * Test that all opening events have corresponding closing events.
     * 
     * This test verifies that for every event of type "O" (open), there is a corresponding
     * event of type "C" (close), ensuring proper frame balance in the profile.
     */
    public function testOpeningEventsHaveCorrespondingClosingEvents(): void
    {
        $log = new ExcimerLog(self::SAMPLE_RAW_LOG);
        $exporter = new SpeedscopeExporter();

        $result = $exporter->export($log);

        // Decode the JSON to check the events
        $data = json_decode($result, true);

        // Get the events from the profile
        $this->assertArrayHasKey('profiles', $data);
        $this->assertNotEmpty($data['profiles']);
        $this->assertArrayHasKey('events', $data['profiles'][0]);

        $events = $data['profiles'][0]['events'];

        // Count the number of opening and closing events
        $openCount = 0;
        $closeCount = 0;

        foreach ($events as $event) {
            $this->assertArrayHasKey('type', $event);

            if ($event['type'] === 'O') {
                $openCount++;
                $this->assertArrayHasKey('frame', $event, 'Opening event must have a frame index');
            } elseif ($event['type'] === 'C') {
                $closeCount++;
                $this->assertArrayHasKey('frame', $event, 'Closing event must have a frame index');
            }
        }

        // Verify that the number of opening events equals the number of closing events
        $this->assertSame($openCount, $closeCount, 'Number of opening events must equal number of closing events');

        // Verify that the events are properly balanced
        $openFrames = 0;

        foreach ($events as $event) {
            if ($event['type'] === 'O') {
                $openFrames++;
            } elseif ($event['type'] === 'C') {
                $openFrames--;
            }

            // The number of open frames should never be negative
            $this->assertGreaterThanOrEqual(0, $openFrames, 'Found a closing event without a corresponding opening event');
        }

        // At the end, all frames should be closed
        $this->assertSame(0, $openFrames, 'Not all opening events have corresponding closing events');
    }

    /**
     * Test that the events in the Speedscope format have the correct structure.
     * 
     * This test verifies that the events array has the correct structure according to the
     * Speedscope format, including:
     * - The 'frame' property is present in both opening and closing events
     * - The 'at' values are incremental and different for each event
     * - Opening and closing events are properly interleaved
     */
    public function testEventsHaveCorrectStructure(): void
    {
        $log = new ExcimerLog(self::SAMPLE_RAW_LOG);
        $exporter = new SpeedscopeExporter();

        $result = $exporter->export($log);

        // Decode the JSON to check the events
        $data = json_decode($result, true);

        // Get the events from the profile
        $this->assertArrayHasKey('profiles', $data);
        $this->assertNotEmpty($data['profiles']);
        $this->assertArrayHasKey('events', $data['profiles'][0]);

        $events = $data['profiles'][0]['events'];

        // Check that there are events
        $this->assertNotEmpty($events, 'Events array should not be empty');

        // Check that each event has the required properties
        $previousAt = -1;
        foreach ($events as $event) {
            $this->assertArrayHasKey('type', $event, 'Event must have a type');
            $this->assertArrayHasKey('at', $event, 'Event must have an at value');
            $this->assertArrayHasKey('frame', $event, 'Event must have a frame index');

            // Check that 'at' values are incremental
            $this->assertGreaterThan($previousAt, $event['at'], 'Event at values must be incremental');
            $previousAt = $event['at'];
        }
    }
}
