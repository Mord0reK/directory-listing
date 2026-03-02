<?php

namespace App;

class IconResolver
{
    private array $config;

    public function __construct(string $configFile)
    {
        $json = file_get_contents($configFile);
        $this->config = json_decode($json, true) ?? [];
    }

    public function resolve(string $name, bool $isDir, string $dirPath = ''): string
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

        if (preg_match('/\.svg$/i', $icon)) {
            return '<img src="' . htmlspecialchars('/assets/icons/' . $icon) . '" width="18" height="18" class="file-icon" aria-hidden="true" alt="">';
        }

        return '<i class="bi bi-file-earmark file-icon" style="font-size: 18px; line-height: 18px;"></i>';
    }

    private function loadDirOverride(string $dirPath): ?array
    {
        if ($dirPath === '') {
            return null;
        }
        $overrideFile = rtrim($dirPath, '/') . '/.dir.json';
        if (!file_exists($overrideFile)) {
            return null;
        }
        $json = file_get_contents($overrideFile);
        return json_decode($json, true);
    }
}
