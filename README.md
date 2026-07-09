# MCP PHPCS Server

> A [Model Context Protocol](https://modelcontextprotocol.io) server that brings **PHP_CodeSniffer** into your AI coding workflow — lint PHP against your coding standard and auto-fix violations, straight from any MCP client.

![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)
![Dependencies](https://img.shields.io/badge/dependencies-none-brightgreen)
![License](https://img.shields.io/badge/license-MIT-blue)

Implemented in **pure PHP with zero Composer dependencies**. Drop it into any project or run it standalone — it speaks JSON-RPC 2.0 over stdio and works with Claude Desktop, Claude Code, Cursor, and any other MCP-compatible client.

## Tools

| Tool | Description |
| --- | --- |
| `phpcs_check` | Runs [PHP_CodeSniffer](https://github.com/PHPCSStandards/PHP_CodeSniffer) against a file or directory and returns a readable report of violations. |
| `phpcbf_fix` | Runs PHP Code Beautifier and Fixer (`phpcbf`) to automatically fix fixable violations. |

Both tools take a single required `path` (absolute or project-relative file/directory).

## Requirements

- **PHP 8.1+**
- `phpcs` and `phpcbf` binaries available (via [PHP_CodeSniffer](https://github.com/PHPCSStandards/PHP_CodeSniffer), e.g. a project's `vendor/bin` or a global install)

## Quick start

1. Clone the repository (or copy it into your project):

   ```bash
   git clone https://github.com/larspohlmann/mcp-phpcs-server.git
   ```

2. Make the entrypoint executable:

   ```bash
   chmod +x mcp-phpcs-server/bin/mcp-phpcs
   ```

3. Register it with your MCP client (see below).

## Configuration

Configure via environment variables **or** `config/config.json`. Environment variables take precedence.

| Variable | `config.json` key | Description | Default |
| --- | --- | --- | --- |
| `MCP_PHPCS_PATH` | `phpcsPath` | Path to the `phpcs` binary | `phpcs` on `PATH` |
| `MCP_PHPCBF_PATH` | `phpcbfPath` | Path to the `phpcbf` binary | `phpcbf` on `PATH` |
| `MCP_PHPCS_RULESET` | `rulesetPath` | Path to a coding-standard ruleset | auto-discovered |

If no ruleset is provided, the server searches upward from the current working directory for one of `phpcs.xml`, `phpcs.xml.dist`, or `ruleset.xml`.

## Client setup

### Claude Desktop / Claude Code

Add the server to your MCP config:

```json
{
  "mcpServers": {
    "phpcs": {
      "command": "/absolute/path/to/mcp-phpcs-server/bin/mcp-phpcs",
      "env": {
        "MCP_PHPCS_PATH": "/usr/local/bin/phpcs",
        "MCP_PHPCBF_PATH": "/usr/local/bin/phpcbf",
        "MCP_PHPCS_RULESET": "/path/to/your/phpcs.xml"
      }
    }
  }
}
```

Then ask your assistant something like *"Run phpcs on src/Service/UserService.php and fix what you can."*

## Example tool call

```json
{
  "name": "phpcs_check",
  "arguments": { "path": "src/Service/UserService.php" }
}
```

## Architecture

The server follows a light Domain-Driven Design layout:

- `src/Domain` — tool contracts and result value objects
- `src/Application` — JSON-RPC MCP server and tool registry
- `src/Infrastructure` — process runner, config, ruleset discovery, tool implementations
- `bin/mcp-phpcs` — stdio entrypoint

It implements the MCP methods `initialize`, `tools/list`, and `tools/call`.

## Notes

- STDOUT carries **only** JSON-RPC messages; all logs go to STDERR.
- A non-zero exit code from `phpcs` means violations were found — it is reported as findings, not treated as a fatal error.

## License

[MIT](LICENSE)
