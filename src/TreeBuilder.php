<?php

namespace App;

class TreeBuilder
{
    private string $baseDir;
    private array  $hidden;

    public function __construct(string $baseDir, array $hidden = [])
    {
        $this->baseDir = rtrim($baseDir, '/');
        $this->hidden  = $hidden;
    }

    /**
     * Build a recursive directory tree.
     * Each node: ['name' => string, 'path' => string, 'open' => bool, 'active' => bool, 'children' => array]
     *
     * @param string $currentPath  The current request path (e.g. "docs/api")
     */
    public function build(string $currentPath = ''): array
    {
        $currentPath = trim($currentPath, '/');
        // Build list of ancestor segments for auto-open
        $ancestors = $this->ancestors($currentPath);

        return $this->scanDir('', $ancestors, $currentPath);
    }

    private function scanDir(string $relPath, array $ancestors, string $currentPath): array
    {
        $fullPath = $relPath === '' ? $this->baseDir : $this->baseDir . '/' . $relPath;

        if (!is_dir($fullPath)) {
            return [];
        }

        $entries = scandir($fullPath);
        $nodes   = [];

        foreach ($entries as $entry) {
            if (in_array($entry, $this->hidden, true)) {
                continue;
            }
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $childRel  = $relPath === '' ? $entry : $relPath . '/' . $entry;
            $childFull = $this->baseDir . '/' . $childRel;

            if (!is_dir($childFull)) {
                continue;
            }

            $isActive   = ($childRel === $currentPath);
            $isOpen     = in_array($childRel, $ancestors, true) || $isActive;
            $children   = $this->scanDir($childRel, $ancestors, $currentPath);

            $nodes[] = [
                'name'     => $entry,
                'path'     => $childRel,
                'open'     => $isOpen,
                'active'   => $isActive,
                'children' => $children,
            ];
        }

        usort($nodes, static fn($a, $b) => strnatcasecmp($a['name'], $b['name']));

        return $nodes;
    }

    /**
     * Returns all ancestor paths of $path.
     * e.g. "a/b/c" → ["a", "a/b"]
     */
    private function ancestors(string $path): array
    {
        if ($path === '') {
            return [];
        }
        $parts     = explode('/', $path);
        $ancestors = [];
        $acc       = '';
        foreach (array_slice($parts, 0, -1) as $part) {
            $acc         = $acc === '' ? $part : $acc . '/' . $part;
            $ancestors[] = $acc;
        }
        return $ancestors;
    }
}
