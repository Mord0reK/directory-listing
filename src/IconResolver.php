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
            return $this->renderIcon($this->config['defaults']['directory'] ?? 'bi-folder-fill');
        }

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        
        if (isset($this->config['extensions'][$ext])) {
            return $this->renderIcon($this->config['extensions'][$ext]);
        }

        return $this->renderIcon($this->config['defaults']['file'] ?? 'bi-file-earmark');
    }

    private function renderIcon(string $icon): string
    {
        if (preg_match('/^bi-/', $icon)) {
            return '<i class="bi ' . htmlspecialchars($icon) . ' file-icon" style="font-size: 18px; line-height: 18px;"></i>';
        }

        if (preg_match('/^<svg/', $icon)) {
            return '<span class="file-icon">' . $icon . '</span>';
        }

        if (preg_match('/\.(svg|png|jpe?g|gif|webp|ico)$/i', $icon)) {
            return '<img src="' . htmlspecialchars($this->resolveIconPath($icon)) . '" width="18" height="18" class="file-icon" aria-hidden="true" alt="">';
        }

        return '<i class="bi bi-file-earmark file-icon" style="font-size: 18px; line-height: 18px;"></i>';
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
