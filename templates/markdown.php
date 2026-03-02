<div class="p-6">

    <!-- Breadcrumb -->
    <nav class="flex items-center gap-1 text-xs text-zinc-400 mb-4" aria-label="Breadcrumb">
        <?php foreach ($breadcrumb as $i => $crumb): ?>
            <?php if ($i > 0): ?>
                <span class="text-zinc-600">/</span>
            <?php endif; ?>
            <?php if ($i === count($breadcrumb) - 1): ?>
                <span class="text-zinc-200"><?= htmlspecialchars($crumb['label']) ?></span>
            <?php else: ?>
                <a class="hover:text-zinc-100 transition-colors" href="<?= htmlspecialchars('/' . $crumb['path']) ?>">
                    <?= htmlspecialchars($crumb['label']) ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <!-- Toolbar -->
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-base font-semibold text-zinc-100"><?= htmlspecialchars($title) ?></h1>
        <a href="<?= htmlspecialchars('/' . $requestPath) ?>?raw=1"
           class="text-xs text-zinc-500 hover:text-zinc-300 border border-zinc-700 hover:border-zinc-500 rounded px-2 py-1 transition-colors">
            raw
        </a>
    </div>

    <!-- Markdown content -->
    <article class="markdown-body">
        <?= $html ?>
    </article>
</div>
