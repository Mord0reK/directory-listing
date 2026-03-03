<!DOCTYPE html>
<html lang="en" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($siteName ?? 'Directory Listing') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600&family=Geist+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/style.css?v=2">
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
<script>
(function () {
    // ── Dropdown logic ───────────────────────────────────────────────
    let openDropdown = null;

    function closeDropdown() {
        if (!openDropdown) return;
        openDropdown.classList.add('hidden');
        openDropdown.previousElementSibling?.setAttribute('aria-expanded', 'false');
        openDropdown = null;
    }

    document.addEventListener('click', function (e) {
        const trigger = e.target.closest('.dropdown-trigger');
        if (trigger) {
            e.stopPropagation();
            const menu = trigger.nextElementSibling;
            if (openDropdown && openDropdown !== menu) closeDropdown();
            const isOpen = !menu.classList.contains('hidden');
            if (isOpen) {
                closeDropdown();
            } else {
                menu.classList.remove('hidden');
                trigger.setAttribute('aria-expanded', 'true');
                openDropdown = menu;
            }
            return;
        }

        // Action buttons inside dropdown
        const download = e.target.closest('.action-download');
        const preview  = e.target.closest('.action-preview');
        const info     = e.target.closest('.action-info');

        if (download || preview || info) {
            const wrap = e.target.closest('.dropdown-wrap');
            const btn  = wrap.querySelector('.dropdown-trigger');
            const path = btn.dataset.path;
            const name = btn.dataset.name;
            closeDropdown();

            if (download) { doDownload(path); }
            if (preview)  { openPreview(path, name); }
            if (info)     { openInfo(path, name); }
            return;
        }

        closeDropdown();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeDropdown();
            closeModal();
        }
    });

    // ── Modal logic ──────────────────────────────────────────────────
    const modal      = document.getElementById('file-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalBody  = document.getElementById('modal-body');
    const modalClose = document.getElementById('modal-close');

    function openModal(title, bodyHtml) {
        modalTitle.textContent = title;
        modalBody.innerHTML = bodyHtml;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modalClose.focus();
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modalBody.innerHTML = '';
    }

    modalClose.addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });

    // ── Actions ──────────────────────────────────────────────────────
    function doDownload(path) {
        const a = document.createElement('a');
        a.href = '/?action=download&path=' + encodeURIComponent(path);
        a.download = '';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }

    async function openPreview(path, name) {
        openModal(name, '<p class="text-muted text-sm animate-pulse">Ładowanie…</p>');
        try {
            const res  = await fetch('/?action=preview&path=' + encodeURIComponent(path));
            const data = await res.json();
            if (data.error) {
                modalBody.innerHTML = '<p class="text-red-400 text-sm">' + escHtml(data.error) + '</p>';
                return;
            }
            const pre  = document.createElement('pre');
            const code = document.createElement('code');
            code.className = 'language-' + data.language;
            code.textContent = data.content;
            pre.appendChild(code);
            pre.className = '!m-0 !p-0 !bg-transparent !border-none overflow-x-auto text-xs';
            const meta = document.createElement('p');
            meta.className = 'text-[10px] text-muted mb-3 font-mono';
            meta.textContent = data.lines + ' linii · ' + formatBytes(data.size) + ' · ' + data.language;
            modalBody.innerHTML = '';
            modalBody.appendChild(meta);
            modalBody.appendChild(pre);
            hljs.highlightElement(code);
        } catch (err) {
            modalBody.innerHTML = '<p class="text-red-400 text-sm">Błąd ładowania podglądu.</p>';
        }
    }

    async function openInfo(path, name) {
        openModal(name, '<p class="text-muted text-sm animate-pulse">Ładowanie…</p>');
        try {
            const res  = await fetch('/?action=info&path=' + encodeURIComponent(path));
            const data = await res.json();
            if (data.error) {
                modalBody.innerHTML = '<p class="text-red-400 text-sm">' + escHtml(data.error) + '</p>';
                return;
            }
            const rows = [
                ['Nazwa',        escHtml(data.name)],
                ['Ścieżka',      escHtml(data.path)],
                ['Rozmiar',      formatBytes(data.size) + ' (' + data.size + ' B)'],
                ['Typ MIME',     escHtml(data.mime)],
                ['Rozszerzenie', escHtml(data.extension)],
                ['Modyfikacja',  escHtml(data.mtime)],
                ['Uprawnienia',  escHtml(data.permissions)],
            ];
            const html = '<table class="w-full text-sm border-collapse">'
                + rows.map(([k, v]) =>
                    '<tr class="border-b border-border-subtle">'
                    + '<td class="py-2.5 pr-6 text-muted text-[11px] uppercase tracking-widest font-semibold w-36">' + k + '</td>'
                    + '<td class="py-2.5 font-mono text-[12px] text-base">' + v + '</td>'
                    + '</tr>'
                ).join('')
                + '</table>';
            modalBody.innerHTML = html;
        } catch (err) {
            modalBody.innerHTML = '<p class="text-red-400 text-sm">Błąd ładowania szczegółów.</p>';
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────
    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        const units = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        return (bytes / Math.pow(1024, i)).toFixed(1) + ' ' + units[i];
    }
})();
</script>
</body>
</html>
