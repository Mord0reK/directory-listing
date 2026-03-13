<?php
/** @var array  $entries     File/dir entries */
/** @var array  $breadcrumb  Breadcrumb segments */
/** @var string $requestPath Current path */
/** @var string|null $parentPath Parent directory path */
/** @var string|null $readmeHtml Rendered README HTML (or null) */
?>
<div class="p-6">

    <!-- Breadcrumb -->
    <nav class="sticky top-0 z-10 flex items-center gap-1 px-6 pb-6 mb-2 -mx-6 text-xs bg-bg-base/80 backdrop-blur-md text-muted" aria-label="Breadcrumb">
        <?php foreach ($breadcrumb as $i => $crumb): ?>
            <?php if ($i > 0): ?>
                <span class="text-zinc-700">/</span>
            <?php endif; ?>
            <?php if ($i === count($breadcrumb) - 1): ?>
                <span class="font-medium text-heading"><?= htmlspecialchars($crumb['label']) ?></span>
            <?php else: ?>
                <a class="transition-colors hover:text-heading" href="<?= htmlspecialchars('/' . $crumb['path']) ?>">
                    <?= htmlspecialchars($crumb['label']) ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <!-- File table -->
    <?php if (empty($entries)): ?>
        <p class="italic text-muted">Empty directory</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-border text-muted text-[10px] uppercase tracking-widest font-semibold">
                        <th class="w-8 pb-3 text-left" aria-hidden="true"></th>
                        <th class="pb-3 text-left">Nazwa</th>
                        <th class="w-8 pb-3 text-left" aria-hidden="true"></th>
                        <th class="hidden w-24 pb-3 font-mono text-right sm:table-cell">Rozmiar</th>
                        <th class="hidden pb-3 font-mono text-right w-44 md:table-cell">Ostatnia modyfikacja</th>
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
                        <tr class="relative transition-all duration-200 hover:bg-bg-hover group">
                            <td class="py-2.5 pr-2 cursor-pointer" onclick="window.location='<?= htmlspecialchars($href) ?>'">
                                <?= $entry['icon'] ?>
                            </td>
                            <td class="py-2.5 cursor-pointer" onclick="window.location='<?= htmlspecialchars($href) ?>'">
                                <span class="text-base group-hover:text-heading transition-colors <?= $entry['isDir'] ? 'font-semibold' : '' ?>">
                                    <?= htmlspecialchars($entry['name']) ?>
                                </span>
                            </td>
                            <td class="py-2.5 text-center">
                                <?php if (!$entry['isDir']): ?>
                                    <button type="button" 
                                            class="inline-flex items-center justify-center w-8 h-8 text-green-400 transition-all duration-200 border rounded-lg cursor-pointer action-preview hover:text-green-300 bg-bg-hover hover:bg-green-400/20 border-border hover:border-green-400"
                                            data-path="<?= htmlspecialchars($entryPath) ?>"
                                            data-name="<?= htmlspecialchars($entry['name']) ?>"
                                            title="View code"
                                            onclick="event.stopPropagation(); openPreview(this.dataset.path, this.dataset.name);">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" height="16" width="16">
  <path fill="#1eff00" d="M7.4973 18.2326c-0.23835 0.00025 -0.4687 -0.0864 -0.6478 -0.2437L0.849605 12.73895c-0.10552 -0.0924 -0.19009 -0.2063 -0.248025 -0.33405 -0.057935 -0.1277 -0.08791 -0.2663 -0.08791 -0.40655 0 -0.1403 0.029975 -0.2789 0.08791 -0.40665 0.057935 -0.12775 0.142505 -0.2416 0.248025 -0.334L6.8495 6.0078c0.1965 -0.17195 0.45325 -0.2588 0.71375 -0.24145 0.26055 0.0174 0.50345 0.1375 0.6754 0.334 0.1719 0.19645 0.25875 0.4532 0.24145 0.7137 -0.0174 0.26055 -0.13755 0.5035 -0.33405 0.67545L2.992235 11.99835l5.153365 4.50885c0.15085 0.13185 0.25785 0.3066 0.30675 0.50085 0.04885 0.19425 0.0373 0.3989 -0.03315 0.5864 -0.07045 0.1875 -0.1965 0.3491 -0.3612 0.46315 -0.16475 0.11405 -0.36035 0.17505 -0.5607 0.175Z" stroke-width="0.5"></path>
  <path fill="#1eff00" d="M16.49745 18.2326c-0.2004 0.00015 -0.39605 -0.0608 -0.56085 -0.1748 -0.1648 -0.114 -0.2909 -0.2756 -0.3614 -0.46315 -0.07055 -0.18755 -0.08215 -0.39215 -0.03325 -0.5865 0.04885 -0.1943 0.15585 -0.3691 0.30675 -0.50095l5.15385 -4.50885 -5.1534 -4.50885c-0.1965 -0.17195 -0.3166 -0.4149 -0.33395 -0.67545 -0.0174 -0.2605 0.06945 -0.51725 0.2414 -0.7137 0.17195 -0.1965 0.41485 -0.3166 0.67535 -0.334 0.26055 -0.01735 0.51725 0.0695 0.71375 0.24145l5.99995 5.2499c0.1055 0.0924 0.19005 0.20625 0.248 0.334 0.05795 0.12775 0.0879 0.26635 0.0879 0.40665 0 0.14025 -0.02995 0.27885 -0.0879 0.40655 -0.05795 0.12775 -0.1425 0.24165 -0.248 0.33405L17.1457 17.9889c-0.1792 0.15745 -0.4097 0.24415 -0.64825 0.2437Z" stroke-width="0.5"></path>
  <path fill="#1eff00" d="M9.7478 20.4823c-0.15325 0 -0.30445 -0.03585 -0.44145 -0.10465 -0.13695 -0.0688 -0.25605 -0.1686 -0.3476 -0.29155 -0.0916 -0.1229 -0.15325 -0.2655 -0.17995 -0.41645 -0.0268 -0.15095 -0.0179 -0.30605 0.0259 -0.45295l4.49995 -14.999805c0.0339 -0.12729 0.093 -0.246475 0.1738 -0.350505 0.0808 -0.10403 0.18165 -0.190785 0.29655 -0.255125 0.11495 -0.06434 0.24165 -0.104965 0.3726 -0.11945 0.1309 -0.01449 0.2634 -0.002555 0.38965 0.0351 0.12625 0.037645 0.2436 0.10025 0.3452 0.18409 0.10155 0.08385 0.1853 0.18723 0.2462 0.304025 0.06095 0.11679 0.0978 0.244625 0.1084 0.37592 0.0106 0.131295 -0.00525 0.26338 -0.04665 0.38842L10.69045 19.77915c-0.0605 0.2031 -0.18495 0.38115 -0.3548 0.5078 -0.1698 0.1267 -0.37595 0.1952 -0.58785 0.19535Z" stroke-width="0.5"></path>
</svg>
                                    </button>
                                <?php endif; ?>
                            </td>
                            <td class="py-2.5 text-right text-muted hidden sm:table-cell font-mono text-[12px] cursor-pointer" onclick="window.location='<?= htmlspecialchars($href) ?>'">
                                <?= $sizeStr ?>
                            </td>
                            <td class="py-2.5 text-right text-zinc-500 hidden md:table-cell text-[11px] font-mono cursor-pointer" onclick="window.location='<?= htmlspecialchars($href) ?>'">
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
        <div class="pt-8 mt-4 border-t border-border">
            <div class="flex items-center justify-between mb-6">
                <h2 class="flex items-center gap-2 text-xs font-bold tracking-widest uppercase text-heading">
                    README.md
                </h2>
                <a href="<?= htmlspecialchars('/' . ltrim(($requestPath !== '' ? $requestPath . '/' : '') . 'README.md', '/')) ?>?raw=1"
                   class="text-[10px] font-bold tracking-widest text-muted hover:text-accent transition-colors">
                    Kod źródłowy
                </a>
            </div>
            <article class="max-w-4xl markdown-body">
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
