<?php
/** @var array  $tree        Nested tree from TreeBuilder::build() */
/** @var string $siteName    From config */
/** @var string $requestPath Current request path */
?>
<aside class="w-72 min-h-screen bg-bg-surface border-r border-border flex flex-col flex-shrink-0 overflow-y-auto shadow-2xl z-20">
    <!-- Site name / root link -->
    <div class="px-4 py-4 border-b border-border-subtle group">
        <a href="/" class="flex items-center gap-3 text-heading font-bold text-base hover:text-accent transition-all duration-300">
            <div class="w-8 h-8 rounded-lg bg-accent flex items-center justify-center text-zinc-950 shadow-lg group-hover:scale-110 transition-transform">
                <?= $folderIcon ?? '<i class="bi bi-folder-fill"></i>' ?>
            </div>
            <span class="truncate tracking-tight"><?= htmlspecialchars($siteName) ?></span>
        </a>
    </div>

    <!-- Directory tree header -->
    <div class="px-4 text-[10px] font-bold text-zinc-500 uppercase tracking-[0.2em]">
        Katalogi
    </div>

    <!-- Directory tree -->
    <nav class="flex-1 px-3 py-2 text-[13px] font-medium">
        <?php if (empty($tree)): ?>
            <span class="text-muted px-3 italic">Brak katalogów</span>
        <?php else: ?>
            <div class="tree-container">
                <?php renderTreeNodes($tree, $requestPath, $folderIcon); ?>
            </div>
        <?php endif; ?>
    </nav>

    <!-- Sidebar Footer -->
    <div class="px-6 py-6 border-t border-border-subtle text-[10px] text-zinc-600 font-mono tracking-wider">
        Wersja 1.0.0
    </div>
</aside>

<?php
function renderTreeNodes(array $nodes, string $requestPath, string $folderIcon): void
{
    echo '<ul class="space-y-1">';
    foreach ($nodes as $node) {
        $isActive = $node['active'];
        $hasChildren = !empty($node['children']);

        $baseClass = 'flex items-center gap-3 w-full px-3 py-2.5 rounded-xl text-left transition-all duration-300 group/item relative';
        $itemClass = $baseClass . ' text-muted hover:bg-bg-hover hover:text-heading';
        
        if ($isActive) {
            $itemClass = $baseClass . ' bg-accent/10 text-accent font-semibold shadow-sm';
            $isActiveIndicator = '<div class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-5 bg-accent rounded-r-full"></div>';
        } else {
            $isActiveIndicator = '';
        }

        echo '<li class="relative">';
        echo $isActiveIndicator;
        
        if ($hasChildren) {
            $open = $node['open'] ? ' open' : '';
            echo '<details' . $open . ' class="group/details">';
            echo '<summary class="list-none cursor-pointer outline-none">';
            echo '<div class="flex items-center">';
            echo '<a href="' . htmlspecialchars('/' . $node['path']) . '" class="' . $itemClass . ' flex-1">';
            echo $folderIcon;
            echo '<span class="truncate">' . htmlspecialchars($node['name']) . '</span>';
            echo '</a>';
            echo '<div class="absolute right-4 top-1/2 -translate-y-1/2 transition-transform duration-300 group-open/details:rotate-90 pointer-events-none opacity-40">';
            echo '<svg width="8" height="8" viewBox="0 0 8 8" fill="none" class="text-zinc-500"><path d="M2.5 1.5L4.5 3.5L2.5 5.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            echo '</div>';
            echo '</div>';
            echo '</summary>';
            echo '<div class="ml-4 pl-3 mt-1 border-l border-border-subtle">';
            renderTreeNodes($node['children'], $requestPath, $folderIcon);
            echo '</div>';
            echo '</details>';
        } else {
            echo '<a href="' . htmlspecialchars('/' . $node['path']) . '" class="' . $itemClass . '">';
            echo $folderIcon;
            echo '<span class="truncate">' . htmlspecialchars($node['name']) . '</span>';
            echo '</a>';
        }
        echo '</li>';
    }
    echo '</ul>';
}
?>
