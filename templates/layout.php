<!DOCTYPE html>
<html lang="en" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($siteName ?? 'Directory Listing') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600&family=Geist+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
    <style>
        body { font-family: 'Geist', sans-serif; }
        .font-mono { font-family: 'Geist Mono', monospace; }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.10.0/styles/github-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.10.0/highlight.min.js"></script>
</head>
<body class="bg-bg-base text-base h-full flex flex-row overflow-hidden text-sm">

    <!-- Sidebar -->
    <?php require __DIR__ . '/sidebar.php'; ?>

    <!-- Main content -->
    <div class="flex-1 overflow-y-auto">
        <?= $content ?>
    </div>

<!-- File Actions Modal -->
<div id="file-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <div class="bg-bg-surface border border-border rounded-2xl shadow-2xl w-full max-w-3xl mx-4 max-h-[85vh] flex flex-col">
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-border flex-shrink-0">
            <h2 id="modal-title" class="text-sm font-semibold text-heading truncate"></h2>
            <button id="modal-close" class="text-muted hover:text-heading transition-colors text-lg leading-none" aria-label="Zamknij">&times;</button>
        </div>
        <!-- Body -->
        <div id="modal-body" class="overflow-y-auto flex-1 p-6">
        </div>
    </div>
</div>
</body>
</html>
