(function (window) {
    'use strict';

    var STORAGE_KEY = 'pictshare_uploads';
    var STORAGE_VERSION = 1;

    function readStore() {
        try {
            var raw = window.localStorage.getItem(STORAGE_KEY);
            if (!raw) return { version: STORAGE_VERSION, items: [] };
            var parsed = JSON.parse(raw);
            if (!parsed || typeof parsed !== 'object' || !Array.isArray(parsed.items))
                return { version: STORAGE_VERSION, items: [] };
            return parsed;
        } catch (e) {
            console.warn('PictShareUploads: failed to read store', e);
            return { version: STORAGE_VERSION, items: [] };
        }
    }

    function writeStore(store) {
        try {
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify(store));
            return true;
        } catch (e) {
            console.warn('PictShareUploads: failed to write store', e);
            return false;
        }
    }

    function add(item) {
        if (!item || !item.hash) return false;
        var store = readStore();
        if (store.items.some(function (i) { return i.hash === item.hash; })) return false;
        var entry = {
            hash:        item.hash,
            url:         item.url || '',
            delete_code: item.delete_code || '',
            delete_url:  item.delete_url || '',
            kind:        item.kind || 'file',
            filetype:    item.filetype || null,
            name:        item.name || item.hash,
            size:        typeof item.size === 'number' ? item.size : null,
            uploaded:    item.uploaded || Math.floor(Date.now() / 1000)
        };
        if (item.kind === 'album' && Array.isArray(item.members)) entry.members = item.members;
        store.items.push(entry);
        return writeStore(store);
    }

    function list() { return readStore().items.slice(); }

    function remove(hash) {
        var store = readStore();
        var before = store.items.length;
        store.items = store.items.filter(function (i) { return i.hash !== hash; });
        if (store.items.length === before) return false;
        return writeStore(store);
    }

    function clear() { writeStore({ version: STORAGE_VERSION, items: [] }); }

    function count() { return readStore().items.length; }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c];
        });
    }

    function formatSize(bytes) {
        if (bytes == null) return '';
        var units = ['B','KB','MB','GB'];
        var i = 0;
        var n = bytes;
        while (n >= 1024 && i < units.length - 1) { n /= 1024; i++; }
        return n.toFixed(n < 10 && i > 0 ? 1 : 0) + ' ' + units[i];
    }

    function formatDate(unix) {
        if (!unix) return '';
        var d = new Date(unix * 1000);
        return d.toISOString().slice(0, 16).replace('T', ' ');
    }

    function iconFor(item) {
        if (item.kind === 'album') return '&#128194;';
        var ft = item.filetype || '';
        if (ft.indexOf('image/') === 0) return '&#128247;';
        if (ft.indexOf('video/') === 0) return '&#127916;';
        if (ft.indexOf('audio/') === 0) return '&#127925;';
        return '&#128196;';
    }

    function thumbHtml(item) {
        var ft = item.filetype || '';
        if (item.kind === 'file' && ft.indexOf('image/') === 0) {
            var thumbUrl = '/200x200/forcesize/' + encodeURIComponent(item.hash);
            return '<img src="' + escapeHtml(thumbUrl) + '" alt="" class="uploads-thumb" loading="lazy" />';
        }
        return '<div class="uploads-thumb uploads-thumb-icon">' + iconFor(item) + '</div>';
    }

    function renderStats(container, items) {
        var totalSize = items.reduce(function (s, i) { return s + (i.size || 0); }, 0);
        var byKind = items.reduce(function (m, i) { m[i.kind] = (m[i.kind] || 0) + 1; return m; }, {});
        var parts = [];
        parts.push('<strong>' + items.length + '</strong> item' + (items.length === 1 ? '' : 's'));
        if (totalSize) parts.push('<strong>' + formatSize(totalSize) + '</strong> total');
        Object.keys(byKind).forEach(function (k) { parts.push(byKind[k] + ' ' + k + (byKind[k] === 1 ? '' : 's')); });
        container.innerHTML = parts.join(' &middot; ');
    }

    function renderList(container, items) {
        if (items.length === 0) {
            container.innerHTML = '<div class="alert alert-secondary">No uploads tracked yet. Files you upload from this browser will appear here.</div>';
            return;
        }
        var sorted = items.slice().sort(function (a, b) { return b.uploaded - a.uploaded; });
        var html = '<div class="uploads-grid">';
        sorted.forEach(function (item) {
            var hashSafe = escapeHtml(item.hash);
            var urlSafe  = escapeHtml(item.url);
            var nameSafe = escapeHtml(item.name);
            html += '<div class="uploads-card" data-hash="' + hashSafe + '">';
            html +=   '<div class="uploads-card-select"><input type="checkbox" class="uploads-select" data-hash="' + hashSafe + '" /></div>';
            html +=   '<a href="' + urlSafe + '" target="_blank" rel="noopener" class="uploads-card-thumb">' + thumbHtml(item) + '</a>';
            html +=   '<div class="uploads-card-body">';
            html +=     '<div class="uploads-card-name"><a href="' + urlSafe + '" target="_blank" rel="noopener">' + nameSafe + '</a>';
            if (item.kind === 'album') html += ' <span class="badge bg-info">ALBUM</span>';
            html +=     '</div>';
            html +=     '<div class="uploads-card-meta small text-muted">';
            html +=       escapeHtml(item.hash);
            if (item.size) html += ' &middot; ' + escapeHtml(formatSize(item.size));
            if (item.uploaded) html += ' &middot; ' + escapeHtml(formatDate(item.uploaded));
            html +=     '</div>';
            html +=     '<div class="uploads-card-actions">';
            html +=       '<button type="button" class="btn btn-outline-secondary btn-sm uploads-copy" data-url="' + urlSafe + '">Copy URL</button> ';
            html +=       '<button type="button" class="btn btn-outline-danger btn-sm uploads-delete" data-hash="' + hashSafe + '">Delete</button>';
            html +=     '</div>';
            html +=   '</div>';
            html += '</div>';
        });
        html += '</div>';
        container.innerHTML = html;
    }

    function bindListHandlers(listEl) {
        if (listEl.dataset.bound) return;
        listEl.dataset.bound = '1';
        listEl.addEventListener('click', function (e) {
            var del = e.target.closest && e.target.closest('.uploads-delete');
            if (del) { handleDelete(del.getAttribute('data-hash')); return; }
            var copy = e.target.closest && e.target.closest('.uploads-copy');
            if (copy) { handleCopy(copy.getAttribute('data-url')); return; }
        });
    }

    function getUploadCode() {
        var el = document.getElementById('uploadcode');
        return el ? el.value : '';
    }

    function handleDelete(hash) {
        var items = list();
        var item = items.find(function (i) { return i.hash === hash; });
        if (!item) return;
        if (!window.confirm('Delete ' + hash + ' from the server? This cannot be undone.')) return;
        var url = '/api/delete/' + encodeURIComponent(item.delete_code) + '/' + encodeURIComponent(item.hash);
        fetch(url, { method: 'POST' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.status === 'ok') {
                    remove(hash);
                    refresh();
                    return;
                }
                if (window.confirm('Server returned: ' + (data.reason || 'unknown error') + '. Remove from this list anyway?')) {
                    remove(hash);
                    refresh();
                }
            })
            .catch(function (e) {
                console.warn('delete failed', e);
                if (window.confirm('Network error contacting server. Remove from this list anyway?')) {
                    remove(hash);
                    refresh();
                }
            });
    }

    function handleCopy(url) {
        if (!navigator.clipboard) { window.prompt('Copy URL:', url); return; }
        navigator.clipboard.writeText(url);
    }

    function handleClearAll() {
        if (!window.confirm('Clear all tracked uploads from this browser? This does NOT delete files from the server.')) return;
        clear();
        refresh();
    }

    function bindSelectionHandlers(listEl) {
        var albumBtn = document.getElementById('my-uploads-make-album');
        if (!albumBtn) return;
        function updateBtn() {
            var n = listEl.querySelectorAll('.uploads-select:checked').length;
            albumBtn.disabled = n < 2;
            albumBtn.textContent = n < 2
                ? 'Create Album from Selected'
                : 'Create Album from ' + n + ' Selected';
        }
        if (!listEl.dataset.selBound) {
            listEl.dataset.selBound = '1';
            listEl.addEventListener('change', function (e) {
                if (e.target && e.target.classList && e.target.classList.contains('uploads-select')) updateBtn();
            });
        }
        if (!albumBtn.dataset.bound) {
            albumBtn.dataset.bound = '1';
            albumBtn.addEventListener('click', function () { handleCreateAlbum(listEl); });
        }
        updateBtn();
    }

    function handleCreateAlbum(listEl) {
        var checked = Array.prototype.slice.call(listEl.querySelectorAll('.uploads-select:checked'));
        var hashes  = checked.map(function (cb) { return cb.getAttribute('data-hash'); }).filter(Boolean);
        var items   = list();
        var fileHashes = hashes.filter(function (h) {
            var it = items.find(function (i) { return i.hash === h; });
            return it && it.kind === 'file';
        });
        if (fileHashes.length < 2) {
            alert('Select at least 2 files (not albums) to create an album.');
            return;
        }
        var fd = new FormData();
        fileHashes.forEach(function (h) { fd.append('hashes[]', h); });
        var code = getUploadCode();
        if (code) fd.append('uploadcode', code);
        var btn = document.getElementById('my-uploads-make-album');
        if (btn) { btn.disabled = true; btn.textContent = 'Creating...'; }
        fetch('/api/album', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.status === 'ok') {
                    add({
                        hash:        data.hash,
                        url:         data.url,
                        delete_code: data.delete_code,
                        delete_url:  data.delete_url,
                        kind:        'album',
                        filetype:    null,
                        name:        'Album of ' + fileHashes.length,
                        size:        null,
                        members:     fileHashes
                    });
                    refresh();
                    alert('Album created: ' + data.url);
                } else {
                    alert('Failed to create album: ' + (data.reason || 'unknown error'));
                    refresh();
                }
            })
            .catch(function (e) {
                console.warn('album create failed', e);
                alert('Network error creating album.');
                refresh();
            });
    }

    function refresh() {
        var tabItem    = document.getElementById('my-uploads-tab-item');
        var countBadge = document.getElementById('my-uploads-count');
        var statsEl    = document.getElementById('my-uploads-stats');
        var listEl     = document.getElementById('my-uploads-list');
        var actionsEl  = document.getElementById('my-uploads-actions');
        var clearBtn   = document.getElementById('my-uploads-clear');
        if (!tabItem || !listEl) return;
        var items = list();
        if (items.length === 0) {
            tabItem.style.display = 'none';
            if (actionsEl) actionsEl.style.display = 'none';
        } else {
            tabItem.style.display = '';
            if (actionsEl) actionsEl.style.display = '';
        }
        if (countBadge) countBadge.textContent = items.length;
        if (statsEl)    renderStats(statsEl, items);
        renderList(listEl, items);
        bindListHandlers(listEl);
        bindSelectionHandlers(listEl);
        if (clearBtn && !clearBtn.dataset.bound) {
            clearBtn.dataset.bound = '1';
            clearBtn.addEventListener('click', handleClearAll);
        }
    }

    window.refreshMyUploads = refresh;

    window.PictShareUploads = {
        add: add,
        list: list,
        remove: remove,
        clear: clear,
        count: count,
        refresh: refresh
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', refresh);
    } else {
        refresh();
    }
})(window);
