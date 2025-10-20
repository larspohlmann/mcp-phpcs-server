<?php
declare(strict_types = 1);

namespace Mcp\PhpcsServer\Infrastructure\Tools;

use Mcp\PhpcsServer\Domain\ToolInterface;
use Mcp\PhpcsServer\Domain\ToolResult;
use Mcp\PhpcsServer\Infrastructure\Config;
use Mcp\PhpcsServer\Infrastructure\ProcessRunner;

final class PhpcsCheckTool implements ToolInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly ProcessRunner $runner,
    ) {}

    public function getName(): string
    {
        return 'phpcs_check';
    }

    public function getDescription(): string
    {
        return 'Run PHP_CodeSniffer (phpcs) against a file or directory and return a readable report.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'path' => [
                    'type' => 'string',
                    'description' => 'Absolute or project-relative file/directory to check',
                ],
            ],
            'required' => ['path'],
        ];
    }

    public function call(array $arguments): ToolResult
    {
        $path = (string) ($arguments['path'] ?? '');

        if ('' === $path) {
            return new ToolResult('Missing required argument: path', true);
        }

        $path = $this->normalizePath($path);

        $cmd = escapeshellcmd($this->config->phpcsPath());
        $cmd .= ' --report=json';

        $ruleset = $this->config->rulesetPath();

        if ($ruleset) {
            $cmd .= ' --standard=' . escapeshellarg($ruleset);
        }

        $cmd .= ' ' . escapeshellarg($path);

        $result = $this->runner->run($cmd);

        if (0 !== $result['exitCode'] && '' === $result['stdout']) {
            // phpcs might return non-zero when violations exist; still parse stdout when present
            $message = 'phpcs failed: ' . trim($result['stderr']);

            return new ToolResult('' !== $message ? $message : 'phpcs failed', true);
        }

        $text = $this->formatPhpcsJson($result['stdout']);
        $isError = $this->hasErrors($result['stdout']);

        return new ToolResult($text, $isError);
    }

    private function normalizePath(string $path): string
    {
        if ('.' === $path[0] || '/' === $path[0]) {
            return realpath($path) ?: $path;
        }

        $full = getcwd() . DIRECTORY_SEPARATOR . $path;

        return realpath($full) ?: $path;
    }

    private function hasErrors(string $json): bool
    {
        $data = json_decode($json, true);

        if (!is_array($data)) {
            return false;
        }

        $errors = (int) ($data['totals']['errors'] ?? 0);
        $warnings = (int) ($data['totals']['warnings'] ?? 0);

        return ($errors + $warnings) > 0;
    }

    private function formatPhpcsJson(string $json): string
    {
        $data = json_decode($json, true);

        if (!is_array($data)) {
            return '' !== trim($json) ? $json : 'No output from phpcs.';
        }

        $lines = [];
        $totals = $data['totals'] ?? ['errors' => 0, 'warnings' => 0, 'fixable' => 0];
        $lines[] = sprintf('Errors: %d | Warnings: %d | Fixable: %d', (int) $totals['errors'], (int) $totals['warnings'], (int) $totals['fixable']);

        $files = $data['files'] ?? [];

        foreach ($files as $file => $info) {
            $messages = $info['messages'] ?? [];

            if (!$messages) {
                continue;
            }

            $lines[] = '';
            $lines[] = $file;

            foreach ($messages as $m) {
                $lines[] = sprintf(
                    '  L%-4d C%-3d %-7s %s (%s)',
                    (int) ($m['line'] ?? 0),
                    (int) ($m['column'] ?? 0),
                    strtoupper((string) ($m['type'] ?? '')),
                    (string) ($m['message'] ?? ''),
                    (string) ($m['source'] ?? ''),
                );
            }
        }

        return implode("\n", $lines);
    }
}
