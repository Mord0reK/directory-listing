<!DOCTYPE html>
<html lang="en" class="h-full dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($siteName ?? 'Directory Listing') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600&family=Geist+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css?v=2">
    <style>
        body { font-family: 'Geist', sans-serif; }
        .font-mono { font-family: 'Geist Mono', monospace; }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.10.0/styles/github-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.10.0/highlight.min.js"></script>
</head>
<body class="flex flex-row h-full overflow-hidden text-sm text-base bg-bg-base">

    <!-- Sidebar -->
    <?php require __DIR__ . '/sidebar.php'; ?>

    <!-- Main content -->
    <div class="flex-1 overflow-y-auto">
        <?= $content ?>
    </div>

<!-- File Actions Modal -->
<div id="file-modal" class="fixed inset-0 z-50 items-center justify-center hidden bg-black/60 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <div class="bg-bg-surface border border-border rounded-2xl shadow-2xl w-full max-w-4xl mx-4 max-h-[85vh] flex flex-col">
        <!-- Header -->
        <div class="flex items-center justify-between flex-shrink-0 px-6 py-4 border-b border-border">
            <h2 id="modal-title" class="text-sm font-semibold truncate text-heading"></h2>
            <button id="modal-close" class="text-lg leading-none transition-colors text-muted hover:text-heading" aria-label="Zamknij">&times;</button>
        </div>
        <!-- Body -->
        <div id="modal-body" class="overflow-y-auto flex-1 p-6 min-h-[500px]">
        </div>
    </div>
</div>
<script>
(function () {
    window.openPreview = openPreview;
    window.openInfo = openInfo;
    window.doDownload = doDownload;

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

        // Action buttons inside dropdown or direct button clicks
        const download = e.target.closest('.action-download');
        const preview  = e.target.closest('.action-preview');
        const info     = e.target.closest('.action-info');

        if (download || preview || info) {
            const btn = download || preview || info;
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
        openModal(name, '<div class="flex items-center justify-center h-[468px]"><div class="flex items-center gap-3 text-muted"><svg class="w-5 h-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span>Ładowanie…</span></div></div>');
        try {
            const res  = await fetch('/?action=preview&path=' + encodeURIComponent(path));
            const data = await res.json();
            if (data.error) {
                modalBody.innerHTML = '<p class="text-sm text-red-400">' + escHtml(data.error) + '</p>';
                return;
            }
            
            const actionsDiv = document.createElement('div');
            actionsDiv.className = 'flex items-center justify-between mb-3';
            
            const meta = document.createElement('p');
            meta.className = 'text-[10px] text-muted font-mono';
            meta.textContent = data.lines + ' linii · ' + formatBytes(data.size) + ' · ' + data.language;
            
            const buttonsDiv = document.createElement('div');
            buttonsDiv.className = 'flex items-center gap-2';
            
            const copyBtn = document.createElement('button');
            copyBtn.className = 'inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-muted bg-bg-hover hover:text-heading border border-border hover:border-accent rounded-lg transition-all';
            copyBtn.innerHTML = '<svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>Kopiuj';
            copyBtn.onclick = async function() {
                try {
                    if (navigator.clipboard && window.isSecureContext) {
                        await navigator.clipboard.writeText(data.content);
                    } else {
                        const textArea = document.createElement("textarea");
                        textArea.value = data.content;
                        textArea.style.position = "fixed";
                        textArea.style.left = "-9999px";
                        document.body.appendChild(textArea);
                        textArea.select();
                        document.execCommand("copy");
                        document.body.removeChild(textArea);
                    }
                    copyBtn.innerHTML = '<svg class="w-6 h-6 text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg><span class="text-green-400">Skopiowano</span>';
                    copyBtn.className = 'inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium bg-green-400/10 border border-green-400/30 rounded-lg transition-all';
                    setTimeout(() => {
                        copyBtn.innerHTML = '<svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>Kopiuj';
                        copyBtn.className = 'inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-muted bg-bg-hover hover:text-heading border border-border hover:border-accent rounded-lg transition-all';
                    }, 2000);
                } catch (err) {
                    copyBtn.innerHTML = '<svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>Błąd';
                    setTimeout(() => {
                        copyBtn.innerHTML = '<svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>Kopiuj';
                        copyBtn.className = 'inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-muted bg-bg-hover hover:text-heading border border-border hover:border-accent rounded-lg transition-all';
                    }, 2000);
                }
            };
            
            const downloadBtn = document.createElement('a');
            downloadBtn.href = '/?action=download&path=' + encodeURIComponent(path);
            downloadBtn.download = '';
            downloadBtn.className = 'inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-muted bg-bg-hover hover:text-heading border border-border hover:border-accent rounded-lg transition-all';
            downloadBtn.innerHTML = '<svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>Pobierz';
            
            buttonsDiv.appendChild(copyBtn);
            buttonsDiv.appendChild(downloadBtn);
            
            actionsDiv.appendChild(meta);
            actionsDiv.appendChild(buttonsDiv);
            
            const pre  = document.createElement('pre');
            const code = document.createElement('code');
            code.className = 'language-' + data.language;
            code.textContent = data.content;
            pre.appendChild(code);
            pre.className = '!m-0 !p-4 !bg-zinc-900 !border-none overflow-auto text-xs rounded-lg h-full';
            
            modalBody.innerHTML = '';
            modalBody.appendChild(actionsDiv);
            modalBody.appendChild(pre);
            hljs.highlightElement(code);
        } catch (err) {
            modalBody.innerHTML = '<p class="text-sm text-red-400">Błąd ładowania podglądu.</p>';
        }
    }

    async function openInfo(path, name) {
        openModal(name, '<p class="text-sm text-muted animate-pulse">Ładowanie…</p>');
        try {
            const res  = await fetch('/?action=info&path=' + encodeURIComponent(path));
            const data = await res.json();
            if (data.error) {
                modalBody.innerHTML = '<p class="text-sm text-red-400">' + escHtml(data.error) + '</p>';
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
            modalBody.innerHTML = '<p class="text-sm text-red-400">Błąd ładowania szczegółów.</p>';
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
