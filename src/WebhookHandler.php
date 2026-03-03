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

        // 5. Resolve path as subdirectory within base_dir (content)
        $baseDir = $this->config['base_dir'] ?? '';
        if ($baseDir === '') {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Base directory not configured']);
            exit;
        }

        // 6. Prevent directory traversal attacks
        $path = trim($path, '/');
        if ($path === '' || str_contains($path, '..') || str_contains($path, "\0")) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Invalid path']);
            exit;
        }

        $absolutePath = realpath($baseDir . '/' . $path);

        // 7. Verify the resolved path is within base_dir and exists
        if ($absolutePath === false || !is_dir($absolutePath)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Directory not found']);
            exit;
        }

        $realBaseDir = realpath($baseDir);
        if ($realBaseDir === false || !str_starts_with($absolutePath, $realBaseDir)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Path outside base directory']);
            exit;
        }

        // 8. Configure git safe.directory and execute git pull
        $safeDirCmd = sprintf('git config --global --add safe.directory %s 2>&1', escapeshellarg($absolutePath));
        exec($safeDirCmd, $safeDirOutput, $safeDirExitCode);

        $command = sprintf('git -C %s pull 2>&1', escapeshellarg($absolutePath));
        exec($command, $output, $exitCode);

        // 9. Return JSON with success, exit_code, output[]
        echo json_encode([
            'success'   => $exitCode === 0,
            'exit_code' => $exitCode,
            'output'    => $output,
        ]);
        exit;
    }
}
