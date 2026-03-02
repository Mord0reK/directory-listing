# Design: File Actions Dropdown Menu

**Date:** 2026-03-02  
**Feature:** Per-file action buttons (download, code preview, file details)

---

## Overview

Add a `⋮` dropdown menu button to each file row in the directory listing. Clicking it opens a small menu with three actions:
1. **Pobierz** — download the file
2. **Podgląd kodu** — view file contents with syntax highlighting (modal)
3. **Szczegóły** — view file metadata (modal)

Folders do not get the dropdown (no download/preview for directories).

---

## UI / UX

- The `⋮` button appears in the last column of the file row
- It becomes fully visible on row hover (using existing `group-hover` pattern), dimly visible otherwise
- Clicking opens a small floating dropdown panel positioned relative to the button
- Dropdown closes on: outside click, Escape key, or selecting an action
- A single shared `<div id="file-modal">` handles both preview and details views
- Modal closes on: backdrop click, Escape key, or close button

---

## Backend — New Endpoints

Added to `Router.php`, detected via query param `?action=`:

| Action | URL | Response |
|--------|-----|----------|
| Download | `?action=download&path=<path>` | File with `Content-Disposition: attachment` |
| Preview | `?action=preview&path=<path>` | JSON: `{content, language, lines, size}` |
| Info | `?action=info&path=<path>` | JSON: `{name, size, mime, mtime, permissions}` |

All endpoints pass paths through the existing `resolveSafe()` method for path traversal protection.  
Preview is limited to text files ≤ 1MB. Binary files return an error message.

---

## Frontend

- Vanilla JS (no frameworks)
- **highlight.js** loaded from CDN for syntax highlighting in preview modal
- Tailwind classes used throughout (consistent with existing design)
- All JS inlined in `layout.php` (no separate JS files needed)
- Dropdown positioning: absolute, right-aligned to the `⋮` button

---

## File Changes

1. `src/Router.php` — add `download`, `preview`, `info` action handlers; dispatch before existing routing
2. `templates/listing.php` — add `⋮` button column to table; add dropdown HTML per row
3. `templates/layout.php` — add modal HTML; add highlight.js CDN link; add JS logic
