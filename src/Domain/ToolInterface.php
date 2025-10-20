<?php
declare(strict_types = 1);

namespace Mcp\PhpcsServer\Domain;

interface ToolInterface
{
    public function getName(): string;

    public function getDescription(): string;

    /**
     * Returns JSON Schema for input arguments as an associative array.
     *
     * @return array<string,mixed>
     */
    public function getInputSchema(): array;

    /**
     * Execute the tool.
     *
     * @param array<string,mixed> $arguments
     */
    public function call(array $arguments): ToolResult;
}
