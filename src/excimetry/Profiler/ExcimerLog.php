<?php

declare(strict_types=1);

namespace Excimetry\Profiler;

/**
 * Represents the profiling data from the Excimer profiler.
 * 
 * This class wraps the raw log data from the Excimer profiler and provides
 * methods for accessing and formatting the data in various ways.
 */
final class ExcimerLog
{
    /**
     * @var string The raw log data from the Excimer profiler
     */
    private string $rawLog;

    /**
     * @var array|null Cached parsed log data
     */
    private ?array $parsedLog = null;

    /**
     * @var array Additional metadata for the profile
     */
    private array $metadata = [];

    /**
     * Create a new ExcimerLog instance.
     * 
     * @param string $rawLog The raw log data from the Excimer profiler
     * @param array $metadata Additional metadata for the profile
     */
    public function __construct(string $rawLog, array $metadata = [])
    {
        $this->rawLog = $rawLog;
        $this->metadata = $metadata;
    }

    /**
     * Get the raw log data.
     * 
     * @return string The raw log data
     */
    public function getRawLog(): string
    {
        return $this->rawLog;
    }

    /**
     * Get the parsed log data.
     * 
     * @return array The parsed log data
     */
    public function getParsedLog(): array
    {
        if ($this->parsedLog === null) {
            $this->parsedLog = $this->parseRawLog();
        }

        return $this->parsedLog;
    }

    /**
     * Get the metadata for the profile.
     * 
     * @return array The metadata
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Add metadata to the profile.
     * 
     * @param string $key The metadata key
     * @param mixed $value The metadata value
     * @return self
     */
    public function addMetadata(string $key, mixed $value): self
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    /**
     * Format the log data in collapsed format.
     * 
     * @return string The log data in collapsed format
     */
    public function formatCollapsed(): string
    {
        $collapsed = [];
        $parsedLog = $this->getParsedLog();

        foreach ($parsedLog as $entry) {
            $stackTrace = $entry['stackTrace'];
            $count = $entry['count'];

            if (!isset($collapsed[$stackTrace])) {
                $collapsed[$stackTrace] = 0;
            }

            $collapsed[$stackTrace] += $count;
        }

        $result = '';
        foreach ($collapsed as $stackTrace => $count) {
            $result .= $stackTrace . ' ' . $count . "\n";
        }

        return $result;
    }

    /**
     * Get the log data in speedscope format.
     * 
     * @return array The log data in speedscope format
     */
    public function getSpeedscopeData(): array
    {
        $parsedLog = $this->getParsedLog();
        $profiles = [];
        $frames = [];
        $frameMap = [];

        // Build the frames list
        foreach ($parsedLog as $entry) {
            foreach ($entry['frames'] as $frame) {
                if (!isset($frameMap[$frame])) {
                    $frameIndex = count($frames);
                    $frames[] = [
                        'name' => $frame,
                    ];
                    $frameMap[$frame] = $frameIndex;
                }
            }
        }

        // Build the profile
        $events = [];
        $openFrames = [];
        $sampleTime = 0;

        foreach ($parsedLog as $entry) {
            $stackFrames = array_map(
                fn($frame) => $frameMap[$frame],
                $entry['frames']
            );

            // Close frames that are no longer in the stack
            while (!empty($openFrames) && !in_array(end($openFrames), $stackFrames)) {
                $frameToClose = array_pop($openFrames);
                $events[] = [
                    'type' => 'C',
                    'at' => $sampleTime++,
                    'frame' => $frameToClose,
                ];
            }

            // Open new frames
            foreach ($stackFrames as $frameIndex) {
                if (empty($openFrames) || end($openFrames) !== $frameIndex) {
                    $events[] = [
                        'type' => 'O',
                        'at' => $sampleTime++,
                        'frame' => $frameIndex,
                    ];
                    $openFrames[] = $frameIndex;
                }
            }

            $sampleTime += $entry['count'] - 1; // Subtract 1 because we already incremented for each event
        }

        // Close any remaining open frames
        while (!empty($openFrames)) {
            $frameToClose = array_pop($openFrames);
            $events[] = [
                'type' => 'C',
                'at' => $sampleTime++,
                'frame' => $frameToClose,
            ];
        }

        // Calculate the total count (sum of all entry counts)
        $totalCount = array_sum(array_column($parsedLog, 'count'));

        $profiles[] = [
            'type' => 'evented',
            'name' => 'Excimer Profile',
            'unit' => 'samples',
            'startValue' => 0,
            'endValue' => $totalCount,
            'events' => $events,
        ];

        return [
            'version' => '0.0.1',
            'shared' => [
                'frames' => $frames,
            ],
            'profiles' => $profiles,
            'activeProfileIndex' => 0,
            'exporter' => 'excimetry',
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Parse the raw log data.
     * 
     * @return array The parsed log data
     */
    private function parseRawLog(): array
    {
        $result = [];
        $lines = explode("\n", trim($this->rawLog));

        foreach ($lines as $line) {
            if (empty($line)) {
                continue;
            }

            // Split the line into stack trace and count
            if (preg_match('/^(.+) (\d+)$/', $line, $matches)) {
                $stackTrace = $matches[1];
                $count = (int)$matches[2];

                // Split the stack trace into frames
                $frames = explode(';', $stackTrace);

                // Store the parsed data
                $result[] = [
                    'stackTrace' => $stackTrace,
                    'frames' => $frames,
                    'count' => $count
                ];
            }
        }

        return $result;
    }
}
