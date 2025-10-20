<?php
declare(strict_types = 1);

namespace Mcp\PhpcsServer\Domain;

final class ToolResult
{
    private string $text;

    private bool $isError;

    public function __construct(string $text, bool $isError = false)
    {
        $this->text = $text;
        $this->isError = $isError;
    }

    public function text(): string
    {
        return $this->text;
    }

    public function isError(): bool
    {
        return $this->isError;
    }
}
