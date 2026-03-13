<?php

namespace App;

class DirScanner
{
    private string $baseDir;
    private array  $hidden;
    private array  $dotExceptions;

    public function __construct(string $baseDir, array $hidden = [], array $dotExceptions = [])
    {
        $this->baseDir       = realpath($baseDir);
        $this->hidden        = $hidden;
        $this->dotExceptions = $dotExceptions;
    }

    /**
     * Returns list of entries in $requestPath relative to baseDir.
     * Throws \RuntimeException on path traversal attempt.
     *
     * @return array{name: string, isDir: bool, size: int, mtime: int}[]
     */
    public function scan(string $requestPath): array
    {
        $safePath = $this->resolveSafe($requestPath);

        if (!is_dir($safePath)) {
            throw new \RuntimeException("Not a directory: $requestPath");
        }

        $entries = scandir($safePath);
        $result  = [];

        foreach ($entries as $entry) {
            if (in_array($entry, $this->hidden, true)) {
                continue;
            }
            if (str_starts_with($entry, '.') && !in_array($entry, $this->dotExceptions, true)) {
                continue;
            }

            $fullPath = $safePath . DIRECTORY_SEPARATOR . $entry;
            $isDir    = is_dir($fullPath);

            $result[] = [
                'name'  => $entry,
                'isDir' => $isDir,
                'size'  => $isDir ? 0 : filesize($fullPath),
                'mtime' => filemtime($fullPath),
            ];
        }

        // Directories first, then files; both sorted naturally (1, 2, 10, 11)
        usort($result, static function (array $a, array $b): int {
            if ($a['isDir'] !== $b['isDir']) {
                return $a['isDir'] ? -1 : 1;
            }
            return strnatcasecmp($a['name'], $b['name']);
        });

        return $result;
    }

    /**
     * Resolves a request path safely, rejecting path traversal and hidden files.
     */
    public function resolveSafe(string $requestPath): string
    {
        $clean = ltrim(str_replace(['\\', "\0"], ['/', ''], $requestPath), '/');

        foreach (explode('/', $clean) as $segment) {
            if ($segment === '') continue;
            if (in_array($segment, $this->hidden, true)) {
                throw new \RuntimeException("Access denied: $requestPath", 403);
            }
            if (str_starts_with($segment, '.') && !in_array($segment, $this->dotExceptions, true)) {
                throw new \RuntimeException("Access denied: $requestPath", 403);
            }
        }

        $fullPath = $this->baseDir . DIRECTORY_SEPARATOR . $clean;
        $real     = realpath($fullPath);

        if ($real === false) {
            throw new \RuntimeException("Path not found: $requestPath");
        }

        // Prevent escaping base_dir
        if (strncmp($real, $this->baseDir, strlen($this->baseDir)) !== 0) {
            throw new \RuntimeException("Access denied: $requestPath", 403);
        }

        return $real;
    }

    public function getBaseDir(): string
    {
        return $this->baseDir;
    }
}
