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
        $this->scanner = new DirScanner($config['base_dir'], $config['hidden'], $config['hidden_dot_exceptions'] ?? []);
        $this->md      = new MarkdownRenderer();
        $this->icons   = new IconResolver($config['icons_file'], $config['base_dir']);
        $this->tree    = new TreeBuilder($config['base_dir'], $config['hidden']);
    }

    public function dispatch(): void
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $requestUri = parse_url($requestUri, PHP_URL_PATH);

        if (isset($_GET['action'])) {
            $action = $_GET['action'];
            $path = $_GET['path'] ?? '';

            switch ($action) {
                case 'preview':
                    $this->handlePreview($path);
                    break;
                case 'download':
                    $this->handleDownload($path);
                    break;
                case 'info':
                    $this->handleInfo($path);
                    break;
            }
            return;
        }

        if ($requestUri === '/_webhook/git-pull' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            (new WebhookHandler($this->config))->handle();
            return;
        }

        $rawPath = ltrim(urldecode($requestUri), '/');

        if ($rawPath === '' || $rawPath === 'index.php') {
            $rawPath = '';
        }

        try {
            $safeFull = $this->scanner->resolveSafe($rawPath);
        } catch (\RuntimeException $e) {
            if ($e->getCode() === 403) {
                http_response_code(403);
                echo '<h1>403 Forbidden</h1>';
            } else {
                $this->send404($e->getMessage());
            }
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

    private function serveListing(string $requestPath, string $fullPath): void
    {
        $entries    = $this->scanner->scan($requestPath);
        $breadcrumb = $this->buildBreadcrumb($requestPath);
        $siteName   = $this->config['site_name'];

        foreach ($entries as &$entry) {
            $entryPath = ltrim(($requestPath !== '' ? $requestPath . '/' : '') . $entry['name'], '/');
            $entry['icon'] = $this->icons->resolve($entry['name'], $entry['isDir'], $fullPath, $entryPath);
        }
        unset($entry);

        $parentPath = $requestPath !== '' && $requestPath !== '/'
            ? dirname(ltrim($requestPath, '/'))
            : null;
        if ($parentPath === '.') {
            $parentPath = '';
        }

        $tree = $this->tree->build($requestPath);
        $tree = $this->appendTreeIcons($tree);

        $folderIcon = $this->icons->resolve('', true, $fullPath, trim($requestPath, '/'));

        $readmeHtml = null;
        $readmeFull = $fullPath . '/README.md';
        if (file_exists($readmeFull)) {
            $readmeHtml = $this->md->renderFile($readmeFull);
        }

        $data = compact('entries', 'breadcrumb', 'siteName', 'requestPath', 'parentPath', 'tree', 'readmeHtml', 'folderIcon');
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

        $treeBase = dirname(ltrim($requestPath, '/'));
        if ($treeBase === '.') $treeBase = '';
        $tree = $this->tree->build($treeBase);
        $tree = $this->appendTreeIcons($tree);

        $folderIcon = $this->icons->resolve('', true, $this->config['base_dir'] . '/' . $treeBase, trim($treeBase, '/'));

        $data = compact('html', 'breadcrumb', 'siteName', 'title', 'requestPath', 'parentPath', 'tree', 'folderIcon');
        $this->renderTemplate('markdown', $data);
    }

    private function appendTreeIcons(array $tree): array
    {
        foreach ($tree as &$node) {
            $parentPath = dirname($node['path']);
            if ($parentPath === '.') {
                $parentPath = '';
            }

            $parentDir = $parentPath === ''
                ? $this->config['base_dir']
                : $this->config['base_dir'] . '/' . $parentPath;

            $node['icon'] = $this->icons->resolve($node['name'], true, $parentDir, $node['path']);

            if (!empty($node['children'])) {
                $node['children'] = $this->appendTreeIcons($node['children']);
            }
        }
        unset($node);

        return $tree;
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
            'html' => 'text/html',
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

    private function handlePreview(string $path): void
    {
        header('Content-Type: application/json');
        try {
            $fullPath = $this->scanner->resolveSafe($path);
            if (is_dir($fullPath)) {
                echo json_encode(['error' => 'Cannot preview directories']);
                return;
            }
            $content  = file_get_contents($fullPath);
            $language = $this->getLanguageFromExtension($fullPath);
            $size     = filesize($fullPath);
            $lines    = count(explode("\n", $content));

            echo json_encode([
                'content'  => $content,
                'language' => $language,
                'size'     => $size,
                'lines'    => $lines,
            ]);
        } catch (\RuntimeException $e) {
            http_response_code($e->getCode() ?: 500);
            echo json_encode(['error' => $e->getCode() === 403 ? 'Access denied' : 'Cannot read file']);
        }
    }

    private function handleDownload(string $path): void
    {
        try {
            $fullPath = $this->scanner->resolveSafe($path);
            if (is_dir($fullPath)) {
                http_response_code(400);
                exit;
            }
            $ext  = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            $mime = $this->mimeType($ext);
            header('Content-Type: ' . $mime);
            header('Content-Length: ' . filesize($fullPath));
            header('Content-Disposition: attachment; filename="' . basename($fullPath) . '"');
            readfile($fullPath);
        } catch (\RuntimeException $e) {
            http_response_code($e->getCode() === 403 ? 403 : 404);
        }
        exit;
    }

    private function handleInfo(string $path): void
    {
        header('Content-Type: application/json');
        try {
            $fullPath = $this->scanner->resolveSafe($path);
            $ext      = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            $mime     = $this->mimeType($ext);

            echo json_encode([
                'name'        => basename($fullPath),
                'path'        => $path,
                'size'        => filesize($fullPath),
                'mime'        => $mime,
                'extension'   => $ext,
                'mtime'       => date('Y-m-d H:i:s', filemtime($fullPath)),
                'permissions' => substr(sprintf('%o', fileperms($fullPath)), -4),
            ]);
        } catch (\RuntimeException $e) {
            http_response_code($e->getCode() ?: 500);
            echo json_encode(['error' => $e->getCode() === 403 ? 'Access denied' : 'Cannot get file info']);
        }
    }

    private function getLanguageFromExtension(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $map = [
            'js'   => 'javascript',
            'ts'   => 'typescript',
            'jsx'  => 'jsx',
            'tsx'  => 'tsx',
            'php'  => 'php',
            'py'   => 'python',
            'rb'   => 'ruby',
            'java' => 'java',
            'c'    => 'c',
            'cpp'  => 'cpp',
            'cs'   => 'csharp',
            'go'   => 'go',
            'rs'   => 'rust',
            'html' => 'html',
            'htm'  => 'html',
            'css'  => 'css',
            'scss' => 'scss',
            'less' => 'less',
            'json' => 'json',
            'xml'  => 'xml',
            'yaml' => 'yaml',
            'yml'  => 'yaml',
            'sql'  => 'sql',
            'sh'   => 'bash',
            'bash' => 'bash',
            'md'   => 'markdown',
            'txt'  => 'plaintext',
        ];
        return $map[$ext] ?? 'plaintext';
    }
}
