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
        $this->icons   = new IconResolver($config['icons_file'], $config['base_dir']);
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

        // Render inline README if present
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

        // For markdown pages, the requestPath is a file; the tree should highlight the parent dir
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
}
