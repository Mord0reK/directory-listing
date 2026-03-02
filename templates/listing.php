<?php
/** @var array  $entries     File/dir entries */
/** @var array  $breadcrumb  Breadcrumb segments */
/** @var string $requestPath Current path */
/** @var string|null $parentPath Parent directory path */
/** @var string|null $readmeHtml Rendered README HTML (or null) */
?>
<div class="p-6">

    <!-- Breadcrumb -->
    <nav class="sticky top-0 z-10 bg-bg-base/80 backdrop-blur-md pb-6 mb-2 -mx-6 px-6 flex items-center gap-1 text-xs text-muted" aria-label="Breadcrumb">
        <?php foreach ($breadcrumb as $i => $crumb): ?>
            <?php if ($i > 0): ?>
                <span class="text-zinc-700">/</span>
            <?php endif; ?>
            <?php if ($i === count($breadcrumb) - 1): ?>
                <span class="text-heading font-medium"><?= htmlspecialchars($crumb['label']) ?></span>
            <?php else: ?>
                <a class="hover:text-heading transition-colors" href="<?= htmlspecialchars('/' . $crumb['path']) ?>">
                    <?= htmlspecialchars($crumb['label']) ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <!-- File table -->
    <?php if (empty($entries)): ?>
        <p class="text-muted italic">Empty directory</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-border text-muted text-[10px] uppercase tracking-widest font-semibold">
                        <th class="w-8 pb-3 text-left" aria-hidden="true"></th>
                        <th class="pb-3 text-left">Nazwa</th>
                        <th class="pb-3 text-right w-24 hidden sm:table-cell font-mono">Rozmiar</th>
                        <th class="pb-3 text-right w-44 hidden md:table-cell font-mono">Ostatnia modyfikacja</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-subtle">
                    <?php foreach ($entries as $entry): ?>
                        <?php
                            $entryPath = ltrim(($requestPath !== '' ? $requestPath . '/' : '') . $entry['name'], '/');
                            $href = '/' . $entryPath;
                            $sizeStr = $entry['isDir'] ? '—' : formatBytes($entry['size']);
                            $dateStr = date('Y-m-d H:i', $entry['mtime']);
                        ?>
                        <tr class="hover:bg-bg-hover transition-all duration-200 group relative cursor-pointer" onclick="window.location='<?= htmlspecialchars($href) ?>'">
                            <td class="py-2.5 pr-2" aria-hidden="true">
                                <?= $entry['icon'] ?>
                            </td>
                            <td class="py-2.5">
                                <span class="text-base group-hover:text-heading transition-colors <?= $entry['isDir'] ? 'font-semibold' : '' ?>">
                                    <?= htmlspecialchars($entry['name']) ?>
                                </span>
                            </td>
                            <td class="py-2.5 text-right text-muted hidden sm:table-cell font-mono text-[11px]">
                                <?= $sizeStr ?>
                            </td>
                            <td class="py-2.5 text-right text-zinc-500 hidden md:table-cell text-[11px] font-mono">
                                <?= $dateStr ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <p class="mt-4 text-[10px] text-zinc-500 uppercase tracking-wider font-semibold">
            <?= count($entries) ?> Element<?= count($entries) !== 1 ? 'ów' : '' ?>
        </p>
    <?php endif; ?>

    <!-- Inline README -->
    <?php if (!empty($readmeHtml)): ?>
        <div class="mt-4 border-t border-border pt-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xs font-bold text-heading uppercase tracking-widest flex items-center gap-2">
                    <i class="bi bi-filetype-md"></i>
                    README.md
                </h2>
                <a href="<?= htmlspecialchars('/' . ltrim(($requestPath !== '' ? $requestPath . '/' : '') . 'README.md', '/')) ?>?raw=1"
                   class="text-[10px] font-bold tracking-widest text-muted hover:text-accent transition-colors">
                    Kod źródłowy
                </a>
            </div>
            <article class="markdown-body max-w-4xl">
                <?= $readmeHtml ?>
            </article>
        </div>
    <?php endif; ?>
</div>

<?php
function formatBytes(int $bytes): string {
    if ($bytes === 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = (int) floor(log($bytes, 1024));
    return round($bytes / (1024 ** $i), 1) . ' ' . $units[$i];
}
?>
