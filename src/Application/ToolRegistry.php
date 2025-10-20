<?php
declare(strict_types = 1);

namespace Mcp\PhpcsServer\Application;

use Mcp\PhpcsServer\Domain\ToolInterface;

final class ToolRegistry
{
    /** @var array<string,ToolInterface> */
    private array $toolsByName = [];

    /** @param array<ToolInterface> $tools */
    public function __construct(array $tools)
    {
        foreach ($tools as $tool) {
            $this->toolsByName[$tool->getName()] = $tool;
        }
    }

    /** @return array<ToolInterface> */
    public function all(): array
    {
        return array_values($this->toolsByName);
    }

    public function get(string $name): ?ToolInterface
    {
        return $this->toolsByName[$name] ?? null;
    }
}
