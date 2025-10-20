<?php
declare(strict_types = 1);

namespace Mcp\PhpcsServer\Infrastructure;

final class Config
{
    public function __construct(
        private readonly ?string $phpcsPath,
        private readonly ?string $phpcbfPath,
        private readonly ?string $rulesetPath,
    ) {}

    public static function fromEnvironment(string $configDir, RulesetLocator $locator): self
    {
        $fileCfg = [];
        $jsonPath = rtrim($configDir, '/\\') . '/config.json';

        if (is_file($jsonPath)) {
            $decoded = json_decode((string) file_get_contents($jsonPath), true);

            if (is_array($decoded)) {
                /** @var array{phpcsPath?:?string, phpcbfPath?:?string, rulesetPath?:?string} $fileCfg */
                $fileCfg = $decoded;
            }
        }

        $phpcs = getenv('MCP_PHPCS_PATH') ?: ($fileCfg['phpcsPath'] ?? null);
        $phpcbf = getenv('MCP_PHPCBF_PATH') ?: ($fileCfg['phpcbfPath'] ?? null);
        $ruleset = getenv('MCP_PHPCS_RULESET') ?: ($fileCfg['rulesetPath'] ?? null) ?: $locator->locate();

        return new self($phpcs ?: null, $phpcbf ?: null, $ruleset ?: null);
    }

    public function phpcsPath(): string
    {
        return $this->phpcsPath ?? 'phpcs';
    }

    public function phpcbfPath(): string
    {
        return $this->phpcbfPath ?? 'phpcbf';
    }

    public function rulesetPath(): ?string
    {
        return $this->rulesetPath;
    }
}
