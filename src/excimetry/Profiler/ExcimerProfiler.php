<?php

declare(strict_types=1);

namespace Excimetry\Profiler;

/**
 * Wrapper for the native ExcimerProfiler.
 * 
 * This class provides a high-level interface for the native ExcimerProfiler,
 * with additional features like configuration options and metadata handling.
 */
final class ExcimerProfiler implements ProfilerInterface
{
    /**
     * @var object The native ExcimerProfiler instance or a compatible mock
     */
    private object $profiler;

    /**
     * @var float The sampling period in seconds
     */
    private float $period = 0.01; // Default: 10ms

    /**
     * @var string The profiling mode
     */
    private string $mode = 'wall'; // Default: wall time

    /**
     * @var bool Whether the profiler is running
     */
    private bool $running = false;

    /**
     * @var array Metadata to include with the profile
     */
    private array $metadata = [];

    /**
     * Create a new ExcimerProfiler instance.
     * 
     * @param array $options Configuration options
     * @throws \RuntimeException If the native ExcimerProfiler class is not available
     */
    public function __construct(array $options = [])
    {
        // Only use the native ExcimerProfiler class
        if (!class_exists('\ExcimerProfiler')) {
            throw new \RuntimeException('The native ExcimerProfiler extension is required but not available.');
        }

        $this->profiler = new \ExcimerProfiler();

        // Apply configuration options
        if (isset($options['period'])) {
            $this->setPeriod($options['period']);
        }

        if (isset($options['mode'])) {
            $this->setMode($options['mode']);
        }

        if (isset($options['metadata'])) {
            $this->metadata = $options['metadata'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setPeriod(float $seconds): self
    {
        $this->period = $seconds;
        $this->profiler->setPeriod($seconds);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setMode(string $mode): self
    {
        if (!in_array($mode, ['wall', 'cpu'])) {
            throw new \InvalidArgumentException(
                "Invalid profiling mode: {$mode}. Supported modes are 'wall' and 'cpu'."
            );
        }

        $this->mode = $mode;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function start(): self
    {
        $this->profiler->start();
        $this->running = true;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function stop(): self
    {
        if ($this->running) {
            $this->profiler->stop();
            $this->running = false;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function reset(): self
    {
        $this->stop();

        // If the profiler is a mock with a reset method, call it
        if (method_exists($this->profiler, 'reset')) {
            $this->profiler->reset();
        } else {
            // Otherwise, create a new instance of the native ExcimerProfiler
            $this->profiler = new \ExcimerProfiler();
        }

        $this->profiler->setPeriod($this->period);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLog(): ExcimerLog
    {
        $result = $this->profiler->getLog();

        // Add standard metadata
        $metadata = array_merge([
            'timestamp' => time(),
            'period' => $this->period,
            'mode' => $this->mode,
            'php_version' => PHP_VERSION,
            'os' => PHP_OS,
        ], $this->metadata);

        // If the result is already our ExcimerLog class, just return it with updated metadata
        if ($result instanceof ExcimerLog) {
            // Create a new instance with the same raw log but updated metadata
            return new ExcimerLog($result->getRawLog(), $metadata);
        }

        if ($result instanceof \ExcimerLog) {
            // The native ExcimerLog object is iterable, with each entry representing a single sample
            // Each entry has a getTrace() method that returns the stack trace for that sample
            $rawLog = '';
            foreach ($result as $entry) {
                $trace = $entry->getTrace();
                // Reverse the trace to get root-to-leaf order
                $trace = array_reverse($trace);

                // Build the stack trace string
                $stackParts = [];
                foreach ($trace as $frame) {
                    if (isset($frame['class'])) {
                        $stackParts[] = $frame['class'];
                    }
                    if (isset($frame['function']) && $frame['function'] !== '{main}') {
                        $stackParts[] = $frame['function'];
                    } else if (isset($frame['function'])) {
                        $stackParts[] = 'main';
                    }
                }

                // Add the stack trace and count to the raw log
                $rawLog .= implode(';', $stackParts) . ' 1' . "\n";
            }

            return new ExcimerLog(trim($rawLog), $metadata);
        }

        // Otherwise, create a new ExcimerLog object with the raw log string
        return new ExcimerLog((string)$result, $metadata);
    }

    /**
     * {@inheritdoc}
     */
    public function isRunning(): bool
    {
        return $this->running;
    }

    /**
     * Add metadata to include with the profile.
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
     * Set multiple metadata values at once.
     * 
     * @param array $metadata The metadata to set
     * @return self
     */
    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    /**
     * Get the current metadata.
     * 
     * @return array The current metadata
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
