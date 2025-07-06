<?php

declare(strict_types=1);

namespace Excimetry\CLI;

use Excimetry\Profiler\ExcimerProfiler;
use Excimetry\Exporter\CollapsedExporter;
use Excimetry\Exporter\SpeedscopeExporter;
use Excimetry\Backend\FileBackend;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command-line tool for profiling PHP scripts.
 */
final class ProfileCommand extends Command
{
    /**
     * @var ExcimerProfiler The profiler instance
     */
    private ExcimerProfiler $profiler;

    /**
     * Create a new ProfileCommand instance.
     */
    public function __construct()
    {
        parent::__construct('profile');
    }

    /**
     * Configure the command.
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Profile a PHP script')
            ->addArgument(
                'script',
                InputArgument::REQUIRED,
                'The script to profile'
            )
            ->addArgument(
                'script-args',
                InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                'Arguments to pass to the script'
            )
            ->addOption(
                'period',
                'p',
                InputOption::VALUE_REQUIRED,
                'Sampling period in seconds',
                0.01
            )
            ->addOption(
                'mode',
                'm',
                InputOption::VALUE_REQUIRED,
                'Profiling mode: wall or cpu',
                'wall'
            )
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_REQUIRED,
                'Output format: speedscope or collapsed',
                'speedscope'
            )
            ->addOption(
                'output',
                'o',
                InputOption::VALUE_REQUIRED,
                'Output directory',
                'profiles'
            );
    }

    /**
     * Execute the command.
     *
     * @param InputInterface $input The input interface
     * @param OutputInterface $output The output interface
     * @return int The exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $script = $input->getArgument('script');
        $scriptArgs = $input->getArgument('script-args');

        // Check if the script exists
        if (!file_exists($script)) {
            $output->writeln("<error>Error: Script not found: {$script}</error>");
            return Command::FAILURE;
        }

        // Create the profiler
        $profilerOptions = [
            'period' => (float)$input->getOption('period'),
            'mode' => $input->getOption('mode'),
        ];

        $this->profiler = new ExcimerProfiler($profilerOptions);

        // Start the profiler
        $this->profiler->start();

        // Run the script
        $exitCode = $this->runScript($script, $scriptArgs);

        // Stop the profiler
        $this->profiler->stop();

        // Get the profile
        $log = $this->profiler->getLog();

        // Save the profile
        $this->saveProfile($log, $input, $output);

        return $exitCode === 0 ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Run the script.
     * 
     * @param string $script The script to run
     * @param array $args Arguments to pass to the script
     * @return int The exit code
     */
    private function runScript(string $script, array $args = []): int
    {
        // Build the command
        $command = escapeshellcmd(PHP_BINARY) . ' ' . escapeshellarg($script);

        // Add any arguments
        if (!empty($args)) {
            $command .= ' ' . implode(' ', array_map('escapeshellarg', $args));
        }

        // Run the command
        $exitCode = 0;
        passthru($command, $exitCode);

        return $exitCode;
    }

    /**
     * Save the profile.
     * 
     * @param mixed $log The profile log
     * @param InputInterface $input The input interface
     * @param OutputInterface $output The output interface
     * @return void
     */
    private function saveProfile(mixed $log, InputInterface $input, OutputInterface $output): void
    {
        // Determine the format
        $format = $input->getOption('format');

        // Create the exporter
        $exporter = match ($format) {
            'collapsed' => new CollapsedExporter(),
            'speedscope' => new SpeedscopeExporter(),
            default => new SpeedscopeExporter(),
        };

        // Determine the output directory
        $outputDir = $input->getOption('output');

        // Create the backend
        $backend = new FileBackend($exporter, $outputDir);

        // Send the profile
        $result = $backend->send($log);

        if ($result) {
            $output->writeln("<info>Profile saved to {$outputDir}</info>");
        } else {
            $output->writeln("<error>Error: Failed to save profile</error>");
        }
    }
}
