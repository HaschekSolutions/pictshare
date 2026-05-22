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

    window.PictShareUploads = {
        add: add,
        list: list,
        remove: remove,
        clear: clear,
        count: count
    };
})(window);
