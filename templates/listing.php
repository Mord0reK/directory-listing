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
                        <th class="pb-3 w-10" aria-hidden="true"></th>
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
                        <tr class="hover:bg-bg-hover transition-all duration-200 group relative">
                            <td class="py-2.5 pr-2" aria-hidden="true">
                                <div class="transition-transform duration-200 group-hover:scale-110">
                                    <?= $entry['icon'] ?>
                                </div>
                            </td>
                            <td class="py-2.5">
                                <a class="text-base group-hover:text-heading transition-colors <?= $entry['isDir'] ? 'font-semibold' : '' ?>"
                                   href="<?= htmlspecialchars($href) ?>">
                                    <?= htmlspecialchars($entry['name']) ?>
                                </a>
                                <div class="absolute left-0 top-0 bottom-0 w-0.5 bg-accent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            </td>
                            <td class="py-2.5 text-right text-muted hidden sm:table-cell font-mono text-[11px]">
                                <?= $sizeStr ?>
                            </td>
                            <td class="py-2.5 text-right text-zinc-500 hidden md:table-cell text-[11px] font-mono">
                                <?= $dateStr ?>
                            </td>
                            <?php if (!$entry['isDir']): ?>
                            <td class="py-2.5 text-right relative">
                                <div class="dropdown-wrap relative inline-block">
                                    <button
                                        class="dropdown-trigger opacity-0 group-hover:opacity-100 transition-opacity p-1 rounded hover:bg-bg-hover text-muted hover:text-heading"
                                        data-path="<?= htmlspecialchars($entryPath) ?>"
                                        data-name="<?= htmlspecialchars($entry['name']) ?>"
                                        aria-label="Akcje dla <?= htmlspecialchars($entry['name']) ?>"
                                        aria-haspopup="true"
                                        aria-expanded="false"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                            <circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/>
                                        </svg>
                                    </button>
                                    <div class="dropdown-menu hidden absolute right-0 top-full mt-1 z-30 bg-bg-surface border border-border rounded-xl shadow-xl py-1 min-w-[160px] text-sm" role="menu">
                                        <button class="action-download flex items-center gap-2.5 w-full px-4 py-2 text-left text-base hover:bg-bg-hover hover:text-heading transition-colors" role="menuitem">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                            Pobierz
                                        </button>
                                        <button class="action-preview flex items-center gap-2.5 w-full px-4 py-2 text-left text-base hover:bg-bg-hover hover:text-heading transition-colors" role="menuitem">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                                            Podgląd kodu
                                        </button>
                                        <button class="action-info flex items-center gap-2.5 w-full px-4 py-2 text-left text-base hover:bg-bg-hover hover:text-heading transition-colors" role="menuitem">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                            Szczegóły
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <?php else: ?>
                            <td class="py-2.5"></td>
                            <?php endif; ?>
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
        <div class="mt-12 border-t border-border pt-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xs font-bold text-heading uppercase tracking-widest flex items-center gap-2">
                    <img src="/assets/icons/md.svg" width="16" height="16" aria-hidden="true" alt="" class="opacity-70 group-hover:opacity-100 transition-opacity">
                    README.md
                </h2>
                <a href="<?= htmlspecialchars('/' . ltrim(($requestPath !== '' ? $requestPath . '/' : '') . 'README.md', '/')) ?>?raw=1"
                   class="text-[10px] uppercase font-bold tracking-widest text-muted hover:text-accent transition-colors">
                    raw source
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
