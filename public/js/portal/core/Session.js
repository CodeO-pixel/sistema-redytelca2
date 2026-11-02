// js/portal/core/Session.js
// Fork de js/core/Session.js para el portal de cliente.
// Clave de storage: `redytelca_portal_session` (distinta de
// `redytelca_session` del admin). Permite sesión simultánea de admin y
// cliente en el mismo navegador sin colisiones.
//
// Fundamento: Bagisto multi-guard ("an admin and customer can stay
// logged in at the same time"), Yii2 multi-tenant auth ("namespacing
// your storage keys"), dual-session packages.
(function () {
    'use strict';

    var STORAGE_KEY = 'redytelca_portal_session';
    var cachedSession = null;
    var isLoaded = false;
    var clearCallbacks = [];

    function loadFromStorage() {
        if (isLoaded) return cachedSession;
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            if (raw) {
                cachedSession = JSON.parse(raw);
            }
        } catch (e) {
            console.error('[PortalSession] Error parsing session from localStorage', e);
            localStorage.removeItem(STORAGE_KEY);
            cachedSession = null;
        }
        isLoaded = true;
        return cachedSession;
    }

    window.PortalSession = {
        get: function () {
            return loadFromStorage();
        },
        getToken: function () {
            var s = this.get();
            return s ? s.token : null;
        },
        isAuthenticated: function () {
            var s = this.get();
            return s ? !!s.authenticated : false;
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
                console.error('[PortalSession] Error saving session to localStorage', e);
            }
        },
        clear: function () {
            cachedSession = null;
            isLoaded = true;
            try {
                localStorage.removeItem(STORAGE_KEY);
            } catch (e) {
                console.error('[PortalSession] Error removing session from localStorage', e);
            }
            clearCallbacks.forEach(function (fn) {
                try { fn(); } catch (e) { console.error('[PortalSession] Error in clear callback', e); }
            });
        },
        onClear: function (fn) {
            if (typeof fn === 'function') clearCallbacks.push(fn);
        }
    };
})();