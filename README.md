# MCP PHPCS Server (Local, No Dependencies)

A minimal Model Context Protocol (MCP) server implemented in pure PHP that exposes two tools:

- `phpcs_check`: Runs PHP_CodeSniffer and returns a readable report.
- `phpcbf_fix`: Runs PHP Code Beautifier and Fixer.

No Composer dependencies. Designed to be copied into any project.

## Structure (DDD)

- `src/Domain`: Tool contracts and results
- `src/Application`: JSON-RPC MCP server and registry
- `src/Infrastructure`: Process runner, config, ruleset discovery, tool implementations
- `bin/mcp-phpcs`: STDIO entrypoint

## Configure

You can configure via environment variables or `config/config.json`:

- `MCP_PHPCS_PATH` – path to `phpcs` binary (default: `phpcs` on PATH)
- `MCP_PHPCBF_PATH` – path to `phpcbf` binary (default: `phpcbf` on PATH)
- `MCP_PHPCS_RULESET` – path to a ruleset (e.g. `phpcs.xml`)

If no ruleset is provided, the server searches upwards from the current working directory for one of:
`phpcs.xml`, `phpcs.xml.dist`, `ruleset.xml`.

## Run

Mark the script executable once:

```bash
chmod +x mcp/phpcs-server/bin/mcp-phpcs
```

Then configure your MCP client to start the server via stdio using the command:

```bash
/absolute/path/to/mcp/phpcs-server/bin/mcp-phpcs
```

### Example: Claude Desktop config

Add this to your MCP config file:

```json
{
  "mcpServers": {
    "phpcs": {
      "command": "/absolute/path/to/mcp/phpcs-server/bin/mcp-phpcs",
      "env": {
        "MCP_PHPCS_PATH": "/usr/local/bin/phpcs",
        "MCP_PHPCBF_PATH": "/usr/local/bin/phpcbf",
        "MCP_PHPCS_RULESET": "/path/to/your/phpcs.xml"
      }
    }
  }
}
```

The server speaks JSON-RPC 2.0 over STDIN/STDOUT and implements:
- `initialize`
- `tools/list`
- `tools/call`

## Tools

### phpcs_check
Input schema:

```json
{
  "type": "object",
  "properties": {
    "path": {"type": "string"}
  },
  "required": ["path"]
}
```

### phpcbf_fix
Same input schema as `phpcs_check`.

## Notes
- Output to STDOUT is strictly JSON-RPC messages. Logs go to STDERR.
- Non-zero exit codes from `phpcs` are treated as violations (not necessarily fatal errors).
