<?php

declare(strict_types=1);

namespace Excimetry\Exporter;

use Excimetry\Profiler\ExcimerLog;

/**
 * Exporter for the collapsed stack format.
 * 
 * This exporter converts ExcimerLog data to the collapsed stack format used by
 * flamegraph tools like Pyroscope and FlameGraph.
 */
final class CollapsedExporter implements ExporterInterface
{
    /**
     * @var bool Whether to reverse the stack trace (root first)
     */
    private bool $reverseStack;
    
    /**
     * @var string The delimiter to use between frames
     */
    private string $delimiter;
    
    /**
     * Create a new CollapsedExporter instance.
     * 
     * @param bool $reverseStack Whether to reverse the stack trace (root first)
     * @param string $delimiter The delimiter to use between frames
     */
    public function __construct(bool $reverseStack = false, string $delimiter = ';')
    {
        $this->reverseStack = $reverseStack;
        $this->delimiter = $delimiter;
    }
    
    /**
     * {@inheritdoc}
     */
    public function export(ExcimerLog $log): string
    {
        $parsedLog = $log->getParsedLog();
        $collapsed = [];
        
        foreach ($parsedLog as $entry) {
            $frames = $entry['frames'];
            
            // Reverse the stack if needed (root first)
            if ($this->reverseStack) {
                $frames = array_reverse($frames);
            }
            
            // Join the frames with the delimiter
            $stackTrace = implode($this->delimiter, $frames);
            
            // Aggregate counts for identical stack traces
            if (!isset($collapsed[$stackTrace])) {
                $collapsed[$stackTrace] = 0;
            }
            
            $collapsed[$stackTrace] += $entry['count'];
        }
        
        // Format the output
        $result = '';
        foreach ($collapsed as $stackTrace => $count) {
            $result .= $stackTrace . ' ' . $count . "\n";
        }
        
        return $result;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getContentType(): string
    {
        return 'text/plain';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getFileExtension(): string
    {
        return 'txt';
    }
}