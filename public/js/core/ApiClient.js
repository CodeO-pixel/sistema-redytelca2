// js/core/ApiClient.js
(function () {
    'use strict';

    class ApiError extends Error {
        constructor(status, message, errors = null) {
            super(message);
            this.name = 'ApiError';
            this.status = status;
            this.errors = errors;
        }
    }

    window.ApiError = ApiError;

    // Devuelve la sesión correcta según el contexto de la app.
    // - Portal: window.__appContext === 'portal' → PortalSession (clave
    //   redytelca_portal_session).
    // - Admin: sin flag (o cualquier otro valor) → Session (clave
    //   redytelca_session).
    // Fundamento: Bagisto multi-guard, Yii2 multi-tenant. Sesiones
    // separadas por contexto permiten admin + portal abiertos a la vez
    // en el mismo navegador.
    function getSession() {
        if (window.__appContext === 'portal' && window.PortalSession) {
            return window.PortalSession;
        }
        return window.Session;
    }

    function buildHeaders(isFormData = false) {
        const headers = {};
        if (!isFormData) {
            headers['Content-Type'] = 'application/json';
        }
        const session = getSession();
        if (session) {
            const token = session.getToken();
            if (token) {
                headers['X-Session-Token'] = token;
            }
        }
        return headers;
    }

    async function request(method, path, body = null, params = null, isFormData = false) {
        let url = path;
        if (params) {
            const qs = new URLSearchParams(params).toString();
            if (qs) {
                url += (url.includes('?') ? '&' : '?') + qs;
            }
        }

        const options = {
            method: method,
            headers: buildHeaders(isFormData),
            credentials: 'same-origin'
        };

        if (body !== null) {
            options.body = isFormData ? body : JSON.stringify(body);
        }

        let response;
        try {
            response = await fetch(url, options);
        } catch (e) {
            throw new ApiError(0, 'Error de conexión con el servidor.');
        }

        let data;
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            try {
                data = await response.json();
            } catch (e) {
                data = {};
            }
        } else {
            const text = await response.text();
            data = { message: text };
        }

        if (response.ok) {
            return data;
        }

        if (response.status === 401) {
            const session = getSession();
            if (session) session.clear();
            throw new ApiError(401, data.message || 'No autenticado.', data.errors || null);
        }

        throw new ApiError(response.status, data.message || 'Error en la petición.', data.errors || null);
    }

    window.ApiClient = {
        get: function (path, params = null) {
            return request('GET', path, null, params, false);
        },
        post: function (path, body, params = null) {
            return request('POST', path, body, params, false);
        },
        put: function (path, body, params = null) {
            return request('PUT', path, body, params, false);
        },
        delete: function (path, params = null) {
            return request('DELETE', path, null, params, false);
        },
        upload: function (path, formData) {
            return request('POST', path, formData, null, true);
        }
    };
})();