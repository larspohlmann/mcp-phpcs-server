<?php
declare(strict_types = 1);

namespace Mcp\PhpcsServer\Application;

use Mcp\PhpcsServer\Domain\ToolInterface;

final class Server
{
    private ToolRegistry $registry;

    /**
     * @var resource|null
     */
    private $stderr;

    /**
     * @param resource|null $stderr
     */
    public function __construct(
        ToolRegistry $registry,
        $stderr,
    ) {
        $this->registry = $registry;
        $this->stderr = $stderr;
    }

    public function run(): void
    {
        $stdin = fopen('php://stdin', 'r');

        if (false === $stdin) {
            $this->log('Cannot open STDIN');

            return;
        }

        while (!feof($stdin)) {
            $line = fgets($stdin);

            if (false === $line) {
                break;
            }

            $line = trim($line);

            if ('' === $line) {
                continue;
            }

            $this->handleMessage($line);
        }
    }

    private function handleMessage(string $line): void
    {
        $msg = json_decode($line, true);

        if (!is_array($msg)) {
            $this->log('Invalid JSON');

            return;
        }

        $id = $msg['id'] ?? null;
        $method = $msg['method'] ?? null;
        $params = $msg['params'] ?? [];

        if (!is_string($method)) {
            $this->sendError($id, -32600, 'Invalid Request');

            return;
        }

        try {
            switch ($method) {
                case 'initialize':
                    $this->reply($id, [
                        'protocolVersion' => (string) ($params['protocolVersion'] ?? '2025-06-18'),
                        'capabilities' => [
                            'tools' => ['listChanged' => false],
                        ],
                        'serverInfo' => [
                            'name' => 'mcp-phpcs-server',
                            'version' => '0.1.0',
                        ],
                    ]);

                    break;
                case 'tools/list':
                    $tools = array_map(static function (ToolInterface $t) {
                        return [
                            'name' => $t->getName(),
                            'description' => $t->getDescription(),
                            'inputSchema' => $t->getInputSchema(),
                        ];
                    }, $this->registry->all());

                    $this->reply($id, [
                        'tools' => $tools,
                        'nextCursor' => null,
                    ]);

                    break;
                case 'tools/call':
                    $name = (string) ($params['name'] ?? '');
                    $args = (array) ($params['arguments'] ?? []);
                    $tool = $this->registry->get($name);

                    if (!$tool) {
                        $this->sendError($id, -32601, 'Tool not found: ' . $name);

                        break;
                    }

                    $result = $tool->call($args);

                    $this->reply($id, [
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => $result->text(),
                            ],
                        ],
                        'isError' => $result->isError(),
                    ]);

                    break;
                default:
                    $this->sendError($id, -32601, 'Method not found');

                    break;
            }
        } catch (\Throwable $e) {
            $this->sendError($id, -32603, 'Internal error', ['message' => $e->getMessage()]);
        }
    }

    private function reply(string|int|null $id, array $result): void
    {
        $resp = [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result,
        ];
        echo json_encode($resp, JSON_UNESCAPED_SLASHES) . "\n";
    }

    private function sendError(string|int|null $id, int $code, string $message, array $data = []): void
    {
        $resp = [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message,
                'data' => $data,
            ],
        ];
        echo json_encode($resp, JSON_UNESCAPED_SLASHES) . "\n";
    }

    private function log(string $message): void
    {
        fwrite($this->stderr, '[mcp-phpcs] ' . $message . "\n");
    }
}
