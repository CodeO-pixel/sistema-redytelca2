// js/core/Session.js
(function () {
    'use strict';

    const STORAGE_KEY = 'redytelca_session';
    let cachedSession = null;
    let isLoaded = false;
    const clearCallbacks = [];

    function loadFromStorage() {
        if (isLoaded) return cachedSession;
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            if (raw) {
                cachedSession = JSON.parse(raw);
            }
        } catch (e) {
            console.error('[Session] Error parsing session from localStorage', e);
            localStorage.removeItem(STORAGE_KEY);
            cachedSession = null;
        }
        isLoaded = true;
        return cachedSession;
    }

    window.Session = {
        get: function () {
            return loadFromStorage();
        },
        getToken: function () {
            const s = this.get();
            return s ? s.token : null;
        },
        isAuthenticated: function () {
            const s = this.get();
            return s ? !!s.authenticated : false;
        },
        isAdmin: function () {
            const s = this.get();
            return s && Array.isArray(s.roles) ? s.roles.includes(1) : false;
        },
        hasRole: function (roleId) {
            const s = this.get();
            return s && Array.isArray(s.roles) ? s.roles.includes(roleId) : false;
        },
        can: function (moduleName, permiso) {
            const s = this.get();
            if (!s || !Array.isArray(s.modules)) return false;
            const mod = s.modules.find(m => {
                let url = String(m.url || '').toLowerCase();
                if (url === 'permisos') url = 'modulos';
                return url === moduleName;
            });
            if (!mod || !Array.isArray(mod.permisos)) return false;
            return mod.permisos.includes(permiso);
        },
        set: function (data) {
            cachedSession = data;
            isLoaded = true;
            try {
                if (!data || !data.token) {
                    localStorage.removeItem(STORAGE_KEY);
                } else {
                    localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
                }
            } catch (e) {
                console.error('[Session] Error saving session to localStorage', e);
            }
        },
        clear: function () {
            cachedSession = null;
            isLoaded = true;
            try {
                localStorage.removeItem(STORAGE_KEY);
            } catch (e) {
                console.error('[Session] Error removing session from localStorage', e);
            }
            clearCallbacks.forEach(fn => {
                try {
                    fn();
                } catch (e) {
                    console.error('[Session] Error in clear callback', e);
                }
            });
        },
        onClear: function (fn) {
            if (typeof fn === 'function') {
                clearCallbacks.push(fn);
            }
        }
    };
})();