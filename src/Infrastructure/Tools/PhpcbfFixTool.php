<?php
declare(strict_types = 1);

namespace Mcp\PhpcsServer\Infrastructure\Tools;

use Mcp\PhpcsServer\Domain\ToolInterface;
use Mcp\PhpcsServer\Domain\ToolResult;
use Mcp\PhpcsServer\Infrastructure\Config;
use Mcp\PhpcsServer\Infrastructure\ProcessRunner;

final class PhpcbfFixTool implements ToolInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly ProcessRunner $runner,
    ) {}

    public function getName(): string
    {
        return 'phpcbf_fix';
    }

    public function getDescription(): string
    {
        return 'Run PHP_CodeSniffer fixer (phpcbf) for a file or directory and return the summary.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'path' => [
                    'type' => 'string',
                    'description' => 'Absolute or project-relative file/directory to fix',
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

        $cmd = escapeshellcmd($this->config->phpcbfPath());

        $ruleset = $this->config->rulesetPath();

        if ($ruleset) {
            $cmd .= ' --standard=' . escapeshellarg($ruleset);
        }

        $cmd .= ' ' . escapeshellarg($path);

        $result = $this->runner->run($cmd);

        $text = trim($result['stdout'] . "\n" . $result['stderr']);
        $isError = 0 !== $result['exitCode'];

        return new ToolResult('' !== $text ? $text : 'phpcbf finished with exit code ' . $result['exitCode'], $isError);
    }

    private function normalizePath(string $path): string
    {
        if ('.' === $path[0] || '/' === $path[0]) {
            return realpath($path) ?: $path;
        }

        $full = getcwd() . DIRECTORY_SEPARATOR . $path;

        return realpath($full) ?: $path;
    }
}
