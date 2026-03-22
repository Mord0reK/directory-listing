<?php
/** @var array  $tree        Nested tree from TreeBuilder::build() */
/** @var string $siteName    From config */
/** @var string $requestPath Current request path */
?>
<aside class="z-20 flex flex-col flex-shrink-0 min-h-screen overflow-y-auto border-r shadow-2xl w-72 bg-bg-surface border-border">
    <!-- Site name / root link -->
    <div class="px-4 py-4 border-b border-border-subtle group">
        <a href="/" class="flex items-center gap-3 text-base font-bold transition-all duration-300 text-heading hover:text-accent">
            <div class="flex items-center justify-center w-8 h-8 transition-transform rounded-lg shadow-lg bg-accent text-zinc-950 group-hover:scale-110">
                <?= $folderIcon ?>
            </div>
            <span class="tracking-tight truncate"><?= htmlspecialchars($siteName) ?></span>
        </a>
    </div>

    <!-- Directory tree header -->
    <div class="px-4 text-[10px] font-bold text-zinc-500 uppercase tracking-[0.2em]">
        Katalogi
    </div>

    <!-- Directory tree -->
    <nav class="flex-1 px-3 py-2 text-[13px] font-medium">
        <?php if (empty($tree)): ?>
            <span class="px-3 italic text-muted">Brak katalogów</span>
        <?php else: ?>
            <div class="tree-container">
                <?php renderTreeNodes($tree, $requestPath); ?>
            </div>
        <?php endif; ?>
    </nav>

    <!-- Sidebar Footer -->
    <div class="px-6 py-6 border-t border-border-subtle text-[10px] text-zinc-600 font-mono tracking-wider">
        Wersja 1.2.3
    </div>
</aside>

<?php
function renderTreeNodes(array $nodes, string $requestPath): void
{
    echo '<ul class="space-y-1">';
    foreach ($nodes as $node) {
        $isActive = $node['active'];
        $hasChildren = !empty($node['children']);
        $nodeIcon = $node['icon'] ?? '';

        $baseClass = 'flex items-center gap-3 w-full px-3 py-2.5 rounded-xl text-left transition-all duration-300 group/item relative';
        $itemClass = $baseClass . ' text-muted hover:bg-bg-hover hover:text-heading';
        
        if ($isActive) {
            $itemClass = $baseClass . ' bg-accent/10 text-accent font-semibold shadow-sm';
            $isActiveIndicator = '<div class="absolute left-0 w-1 h-5 -translate-y-1/2 rounded-r-full top-1/2 bg-accent"></div>';
        } else {
            $isActiveIndicator = '';
        }

        echo '<li>';
        
        if ($hasChildren) {
            $open = $node['open'] ? ' open' : '';
            echo '<details' . $open . ' class="group/details">';
            echo '<summary class="list-none outline-none cursor-pointer">';
            echo '<div class="flex items-center">';
            $linkContent = $isActiveIndicator . $nodeIcon . '<span class="truncate">' . htmlspecialchars($node['name']) . '</span>';
            echo '<a href="' . htmlspecialchars('/' . $node['path']) . '" class="' . $itemClass . ' flex-1">' . $linkContent . '</a>';
            echo '<div class="absolute transition-transform duration-300 -translate-y-1/2 pointer-events-none right-4 top-1/2 group-open/details:rotate-90 opacity-40">';
            echo '<svg width="8" height="8" viewBox="0 0 8 8" fill="none" class="text-zinc-500"><path d="M2.5 1.5L4.5 3.5L2.5 5.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            echo '</div>';
            echo '</div>';
            echo '</summary>';
            echo '<div class="pl-3 mt-1 ml-4 border-l border-border-subtle">';
            renderTreeNodes($node['children'], $requestPath);
            echo '</div>';
            echo '</details>';
        } else {
            $linkContent = $isActiveIndicator . $nodeIcon . '<span class="truncate">' . htmlspecialchars($node['name']) . '</span>';
            echo '<a href="' . htmlspecialchars('/' . $node['path']) . '" class="' . $itemClass . '">' . $linkContent . '</a>';
        }
        echo '</li>';
    }
    echo '</ul>';
}
?>
