<?php
declare(strict_types = 1);

namespace Mcp\PhpcsServer\Infrastructure;

final class ProcessRunner
{
    /**
     * @var resource|null $stderr
     */
    private $stderr;

    /**
     * @param resource|null $stderr
     */
    public function __construct($stderr)
    {
        $this->stderr = $stderr;
    }

    /**
     * @param array<string,string> $env
     *
     * @return array{exitCode:int, stdout:string, stderr:string}
     */
    public function run(string $command, ?string $cwd = null, array $env = []): array
    {
        $descriptors = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        $process = proc_open($command, $descriptors, $pipes, $cwd ?: null, $env ?: null);

        if (!\is_resource($process)) {
            throw new ProcessRunnerException('Failed to start process');
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $status = proc_get_status($process);
        $exitCode = proc_close($process);

        if ($status['signaled'] ?? false) {
            fwrite($this->stderr, '[process] terminated by signal' . PHP_EOL);
        }

        return [
            'exitCode' => is_int($exitCode) ? $exitCode : ($status['exitcode'] ?? 1),
            'stdout' => false !== $stdout ? $stdout : '',
            'stderr' => false !== $stderr ? $stderr : '',
        ];
    }
}
