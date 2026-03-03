<?php

namespace App;

class WebhookHandler
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function handle(): void
    {
        // 1. Set header Content-Type: application/json
        header('Content-Type: application/json');

        // 2. Check REQUEST_METHOD === POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
            exit;
        }

        // 3. Decode JSON body, extract token and path
        $rawBody = file_get_contents('php://input');
        $payload = json_decode($rawBody, true);

        if (!is_array($payload)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
            exit;
        }

        $token = $payload['token'] ?? '';
        $path  = $payload['path'] ?? '';

        // 4. hash_equals() token validation - 401 if not match
        $expectedToken = $this->config['webhook_token'] ?? '';
        if ($expectedToken === '' || !hash_equals($expectedToken, $token)) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        // 5. Check path in whitelist - 403 if not present
        $webhookPaths = $this->config['webhook_paths'] ?? [];
        if (!isset($webhookPaths[$path])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Forbidden path']);
            exit;
        }

        $absolutePath = $webhookPaths[$path];

        // 6. Execute git pull
        $command = sprintf('git -C %s pull 2>&1', escapeshellarg($absolutePath));
        exec($command, $output, $exitCode);

        // 7. Return JSON with success, exit_code, output[]
        echo json_encode([
            'success'   => $exitCode === 0,
            'exit_code' => $exitCode,
            'output'    => $output,
        ]);
        exit;
    }
}
