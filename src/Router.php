<?php

namespace App;

class Router
{
    private DirScanner     $scanner;
    private MarkdownRenderer $md;
    private IconResolver   $icons;
    private TreeBuilder    $tree;
    private array          $config;

    public function __construct(array $config)
    {
        $this->config  = $config;
        $this->scanner = new DirScanner($config['base_dir'], $config['hidden']);
        $this->md      = new MarkdownRenderer();
        $this->icons   = new IconResolver($config['icons_file']);
        $this->tree    = new TreeBuilder($config['base_dir'], $config['hidden']);
    }

    public function dispatch(): void
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $requestUri = parse_url($requestUri, PHP_URL_PATH);
        $rawPath = ltrim(urldecode($requestUri), '/');

        if ($rawPath === '' || $rawPath === 'index.php') {
            $rawPath = '';
        }

        // Handle API actions
        $action = $_GET['action'] ?? null;
        if ($action !== null) {
            $path = $_GET['path'] ?? '';
            $this->handleAction($action, $path);
            return;
        }

        try {
            $safeFull = $this->scanner->resolveSafe($rawPath);
        } catch (\RuntimeException $e) {
            $this->send404($e->getMessage());
            return;
        }

        if (is_dir($safeFull)) {
            $this->serveListing($rawPath, $safeFull);
            return;
        }

        $ext = strtolower(pathinfo($safeFull, PATHINFO_EXTENSION));

        if ($ext === 'md' && !isset($_GET['raw'])) {
            $this->serveMarkdown($rawPath, $safeFull);
            return;
        }

        $this->serveRaw($safeFull, $ext);
    }

    // ---------------------------------------------------------------

    private function handleAction(string $action, string $path): void
    {
        try {
            $safeFull = $this->scanner->resolveSafe($path);
        } catch (\RuntimeException $e) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }

        if (is_dir($safeFull)) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Cannot perform action on directory']);
            exit;
        }

        match ($action) {
            'download' => $this->actionDownload($safeFull),
            'preview'  => $this->actionPreview($safeFull),
            'info'     => $this->actionInfo($safeFull, $path),
            default    => $this->send404('Unknown action'),
        };
    }

    private function actionDownload(string $fullPath): void
    {
        $ext  = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mime = $this->mimeType($ext);
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($fullPath));
        header('Content-Disposition: attachment; filename="' . basename($fullPath) . '"');
        readfile($fullPath);
        exit;
    }

    private function actionPreview(string $fullPath): void
    {
        header('Content-Type: application/json');
        $maxSize = 1 * 1024 * 1024; // 1 MB
        if (filesize($fullPath) > $maxSize) {
            echo json_encode(['error' => 'File too large to preview (max 1 MB)']);
            exit;
        }
        $content = file_get_contents($fullPath);
        if ($content === false || !mb_check_encoding($content, 'UTF-8')) {
            echo json_encode(['error' => 'Binary file cannot be previewed']);
            exit;
        }
        $ext      = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $language = $this->extToLanguage($ext);
        $lines    = substr_count($content, "\n") + 1;
        echo json_encode([
            'content'  => $content,
            'language' => $language,
            'lines'    => $lines,
            'size'     => filesize($fullPath),
        ]);
        exit;
    }

    private function actionInfo(string $fullPath, string $requestPath): void
    {
        header('Content-Type: application/json');
        $ext  = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mime = $this->mimeType($ext);
        $perms = substr(sprintf('%o', fileperms($fullPath)), -4);
        echo json_encode([
            'name'        => basename($fullPath),
            'path'        => $requestPath,
            'size'        => filesize($fullPath),
            'mime'        => $mime,
            'mtime'       => date('Y-m-d H:i:s', filemtime($fullPath)),
            'permissions' => $perms,
            'extension'   => $ext ?: '(none)',
        ]);
        exit;
    }

    private function extToLanguage(string $ext): string
    {
        $map = [
            'php' => 'php', 'js' => 'javascript', 'ts' => 'typescript',
            'py'  => 'python', 'rb' => 'ruby', 'java' => 'java',
            'cs'  => 'csharp', 'go' => 'go', 'rs' => 'rust',
            'cpp' => 'cpp', 'c' => 'c', 'h' => 'c',
            'css' => 'css', 'scss' => 'scss', 'html' => 'html',
            'xml' => 'xml', 'json' => 'json', 'yaml' => 'yaml',
            'yml' => 'yaml', 'sql' => 'sql', 'sh'  => 'bash',
            'bash' => 'bash', 'md' => 'markdown', 'txt' => 'plaintext',
            'ini' => 'ini', 'toml' => 'toml', 'env' => 'plaintext',
        ];
        return $map[$ext] ?? 'plaintext';
    }

    // ---------------------------------------------------------------

    private function serveListing(string $requestPath, string $fullPath): void
    {
        $entries    = $this->scanner->scan($requestPath);
        $breadcrumb = $this->buildBreadcrumb($requestPath);
        $siteName   = $this->config['site_name'];

        foreach ($entries as &$entry) {
            $entry['icon'] = $this->icons->resolve($entry['name'], $entry['isDir'], $fullPath);
        }
        unset($entry);

        $parentPath = $requestPath !== '' && $requestPath !== '/'
            ? dirname(ltrim($requestPath, '/'))
            : null;
        if ($parentPath === '.') {
            $parentPath = '';
        }

        $tree = $this->tree->build($requestPath);

        // Render inline README if present
        $readmeHtml = null;
        $readmeFull = $fullPath . '/README.md';
        if (file_exists($readmeFull)) {
            $readmeHtml = $this->md->renderFile($readmeFull);
        }

        $data = compact('entries', 'breadcrumb', 'siteName', 'requestPath', 'parentPath', 'tree', 'readmeHtml');
        $this->renderTemplate('listing', $data);
    }

    private function serveMarkdown(string $requestPath, string $fullPath): void
    {
        $html       = $this->md->renderFile($fullPath);
        $breadcrumb = $this->buildBreadcrumb($requestPath);
        $siteName   = $this->config['site_name'];
        $title      = basename($requestPath);

        $parentPath = dirname(ltrim($requestPath, '/'));
        if ($parentPath === '.') {
            $parentPath = '';
        }

        // For markdown pages, the requestPath is a file; the tree should highlight the parent dir
        $treeBase = dirname(ltrim($requestPath, '/'));
        if ($treeBase === '.') $treeBase = '';
        $tree = $this->tree->build($treeBase);
        $data = compact('html', 'breadcrumb', 'siteName', 'title', 'requestPath', 'parentPath', 'tree');
        $this->renderTemplate('markdown', $data);
    }

    private function serveRaw(string $fullPath, string $ext): void
    {
        $mime = $this->mimeType($ext);
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($fullPath));
        header('Content-Disposition: inline; filename="' . basename($fullPath) . '"');
        readfile($fullPath);
        exit;
    }

    private function renderTemplate(string $name, array $data): void
    {
        extract($data);
        $templateDir = __DIR__ . '/../templates';
        ob_start();
        require $templateDir . '/' . $name . '.php';
        $content = ob_get_clean();
        require $templateDir . '/layout.php';
    }

    private function buildBreadcrumb(string $requestPath): array
    {
        $parts  = array_filter(explode('/', trim($requestPath, '/')));
        $crumbs = [['label' => $this->config['site_name'], 'path' => '']];
        $acc    = '';
        foreach ($parts as $part) {
            $acc      .= '/' . $part;
            $crumbs[] = ['label' => $part, 'path' => ltrim($acc, '/')];
        }
        return $crumbs;
    }

    private function send404(string $message): void
    {
        http_response_code(404);
        echo '<h1>404 Not Found</h1><p>' . htmlspecialchars($message) . '</p>';
    }

    private function mimeType(string $ext): string
    {
        $map = [
            'txt'  => 'text/plain',
            'css'  => 'text/css',
            'js'   => 'application/javascript',
            'json' => 'application/json',
            'xml'  => 'application/xml',
            'pdf'  => 'application/pdf',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'svg'  => 'image/svg+xml',
            'ico'  => 'image/x-icon',
            'php'  => 'application/x-httpd-php',
        ];
        return $map[$ext] ?? 'application/octet-stream';
    }
}
