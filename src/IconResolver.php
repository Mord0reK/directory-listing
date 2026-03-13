<?php

namespace App;

class IconResolver
{
    private array $config;
    private ?string $baseDirSegment;

    public function __construct(string $configFile, string $baseDir = '')
    {
        $json = file_get_contents($configFile);
        $this->config = json_decode($json, true) ?? [];

        $normalizedBase = trim(str_replace('\\', '/', $baseDir), '/');
        if ($normalizedBase === '') {
            $this->baseDirSegment = null;
            return;
        }

        $parts = explode('/', $normalizedBase);
        $segment = end($parts);
        $this->baseDirSegment = $segment !== false && $segment !== '' ? $segment : null;
    }

    public function resolve(string $name, bool $isDir, string $dirPath = '', string $relativePath = ''): string
    {
        $override = $this->loadDirOverride($dirPath);
        if ($override !== null) {
            if ($isDir && isset($override['directories'][$name])) {
                return $this->renderIcon($override['directories'][$name]);
            }
            if (!$isDir) {
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (isset($override['extensions'][$ext])) {
                    return $this->renderIcon($override['extensions'][$ext]);
                }
                if (isset($override['files'][$name])) {
                    return $this->renderIcon($override['files'][$name]);
                }
            }
        }

        $normalizedPath = $this->normalizeRelativePath($relativePath);
        $pathIcon = $this->resolvePathIcon($normalizedPath);
        if ($pathIcon !== null) {
            return $this->renderIcon($pathIcon);
        }

        $type = $isDir ? 'directory' : 'file';

        if (isset($this->config['custom'][$type])) {
            return $this->renderIcon($this->config['custom'][$type]);
        }

        if ($isDir) {
            return $this->renderIcon($this->config['defaults']['directory'] ?? 'folder.svg');
        }

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        
        if (isset($this->config['extensions'][$ext])) {
            return $this->renderIcon($this->config['extensions'][$ext]);
        }

        return $this->renderIcon($this->config['defaults']['file'] ?? 'file.svg');
    }

    private function renderIcon(string $icon): string
    {
        $cacheBust = '?v=' . time();
        
        // Map vscode-icons naming to actual SVG file names
        $iconMap = [
            'vscode-folder' => 'default_folder',
            'vscode-markdown' => 'file_type_markdown',
            'vscode-document' => 'file_type_document',
            'vscode-pdf' => 'file_type_pdf',
            'vscode-php' => 'file_type_php',
            'vscode-javascript' => 'file_type_javascript',
            'vscode-typescript' => 'file_type_typescript',
            'vscode-css' => 'file_type_css',
            'vscode-sass' => 'file_type_sass',
            'vscode-less' => 'file_type_less',
            'vscode-html' => 'file_type_html',
            'vscode-json' => 'file_type_json',
            'vscode-xml' => 'file_type_xml',
            'vscode-yaml' => 'file_type_yaml',
            'vscode-shell' => 'file_type_shell',
            'vscode-python' => 'file_type_python',
            'vscode-ruby' => 'file_type_ruby',
            'vscode-go' => 'file_type_go',
            'vscode-rust' => 'file_type_rust',
            'vscode-java' => 'file_type_java',
            'vscode-c' => 'file_type_c',
            'vscode-cpp' => 'file_type_cpp',
            'vscode-database' => 'file_type_sql',
            'vscode-image' => 'file_type_image',
            'vscode-zip' => 'file_type_zip',
            'vscode-dotenv' => 'file_type_dotenv',
            'vscode-log' => 'file_type_log',
            'vscode-csv' => 'file_type_csv',
            'vscode-excel' => 'file_type_excel',
            'vscode-word' => 'file_type_word',
            'vscode-powerpoint' => 'file_type_powerpoint',
            'vscode-exe' => 'file_type_exe',
            'vscode-dll' => 'file_type_dll',
            'vscode-iso' => 'file_type_iso',
            'vscode-audio' => 'file_type_audio',
            'vscode-video' => 'file_type_video',
            'vscode-git' => 'file_type_git',
            'vscode-docker' => 'file_type_docker',
            'vscode-lock' => 'file_type_lock',
            'vscode-gradle' => 'file_type_gradle',
            'vscode-maven' => 'file_type_maven',
            'vscode-default' => 'default_file',
        ];
        
        if (preg_match('/^vscode-/', $icon)) {
            $fileName = $iconMap[$icon] ?? 'default_file';
            $url = "https://raw.githubusercontent.com/vscode-icons/vscode-icons/master/icons/{$fileName}.svg" . $cacheBust;
            return '<img src="' . htmlspecialchars($url) . '" width="24" height="24" class="file-icon" aria-hidden="true">';
        }

        if (preg_match('/^<svg/', $icon)) {
            return '<span class="file-icon">' . $icon . '</span>';
        }

        if (preg_match('/\.(svg|png|jpe?g|gif|webp|ico)$/i', $icon)) {
            return '<img src="' . htmlspecialchars($this->resolveIconPath($icon)) . '" width="24" height="24" class="file-icon" aria-hidden="true" alt="">';
        }

        // Default fallback to vscode folder icon
        $url = "https://raw.githubusercontent.com/vscode-icons/vscode-icons/master/icons/default_file.svg" . $cacheBust;
        return '<img src="' . htmlspecialchars($url) . '" width="20" height="20" class="file-icon" aria-hidden="true">';
    }

    private function resolveIconPath(string $icon): string
    {
        $icon = str_replace('\\', '/', trim($icon));

        if (preg_match('/^(https?:)?\/\//i', $icon)) {
            return $icon;
        }

        if (preg_match('/^\//', $icon)) {
            return $icon;
        }

        $relativeIcon = ltrim($icon, '/');
        if (strpos($relativeIcon, '/') === false) {
            $customFullPath = __DIR__ . '/../assets/icons/custom/' . $relativeIcon;
            if (file_exists($customFullPath)) {
                return '/assets/icons/custom/' . $relativeIcon;
            }
        }

        return '/assets/icons/' . $relativeIcon;
    }

    private function normalizeRelativePath(string $path): ?string
    {
        $path = trim(str_replace('\\', '/', $path));
        $path = trim($path, '/');

        if ($path === '') {
            return '';
        }

        $parts = [];
        foreach (explode('/', $path) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                return null;
            }
            $parts[] = $part;
        }

        return implode('/', $parts);
    }

    private function resolvePathIcon(?string $normalizedPath): ?string
    {
        if ($normalizedPath === null) {
            return null;
        }

        $paths = $this->config['paths'] ?? null;
        if (!is_array($paths)) {
            return null;
        }

        $candidates = [$normalizedPath];

        if ($this->baseDirSegment !== null && $normalizedPath !== '') {
            $prefix = $this->baseDirSegment . '/';
            if (strpos($normalizedPath, $prefix) === 0) {
                $candidates[] = substr($normalizedPath, strlen($prefix));
            } else {
                $candidates[] = $prefix . $normalizedPath;
            }
        }

        foreach ($candidates as $candidate) {
            if (isset($paths[$candidate])) {
                return (string) $paths[$candidate];
            }
        }

        return null;
    }

    private function loadDirOverride(string $dirPath): ?array
    {
        if ($dirPath === '') {
            return null;
        }
        $overrideFile = rtrim($dirPath, '/\\') . '/.dir.json';
        if (!file_exists($overrideFile)) {
            return null;
        }
        $json = file_get_contents($overrideFile);
        return json_decode($json, true);
    }
}
