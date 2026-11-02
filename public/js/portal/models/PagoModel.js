// js/portal/models/PagoModel.js
// Model del portal para "Mis pagos" (096c, list + create + upload).
//
// Contrato backend (api_portal_pagos.php):
//   GET ?page=1&per_page=20&estado=... → {status, pagos:[...], total, page, pages, per_page}
//   GET ?id=N                          → {status, pago:{...}} | 404
//   POST (multipart)                    → {status, message, pago_id}
//
// Contrato backend (api_portal_comprobante.php):
//   GET ?id=N → bytes del archivo (Content-Disposition: attachment).
//   Requiere X-Session-Token. Se sirve vía fetch + blob (no se puede abrir como URL estática).
//
// Paginación server-side (el número de pagos puede crecer).
// Filtros: estado (pendiente|validado|rechazado).
// Los montos son USD; moneda_pago indica la divisa real del pago (USD o VES).
// formatDual(usd, pago) muestra $X (Bs. Y) cuando moneda_pago='VES'.
//
// A2 (2026-11-02): añadido `loadDetail(id)` para el bug del
// `PortalNotificacionController.navigateTo('/portal/pagos?id=N')`. Sin
// `loadDetail`, `PortalPagoController.showDetail` solo leía del cache y
// devolvía "Pago no encontrado" para pagos fuera de la página 1.
// Replica el patrón de `PortalFacturaModel.loadDetail` y
// `PortalTicketModel.loadDetail`. Patrón estándar del repo: si el item
// no está en cache, se pide al backend y se cachea la respuesta.
(function () {
    'use strict';

    window.PortalPagoModel = {
        cache: [],
        total: 0,
        page: 1,
        pages: 1,
        perPage: 20,

        load: async function (page = 1, perPage = 20, filtros = {}) {
            const params = { page: page, per_page: perPage };
            if (filtros.estado) params.estado = filtros.estado;

            const res = await window.ApiClient.get('api_portal_pagos.php', params);

            if (res.status === 'success') {
                this.cache = Array.isArray(res.pagos) ? res.pagos : [];
                this.total = Number(res.total) || 0;
                this.page = Number(res.page) || 1;
                this.pages = Number(res.pages) || 1;
                this.perPage = Number(res.per_page) || perPage;
            }
            return res;
        },

        /**
         * A2: detalle de un pago. Devuelve `null` si el backend
         * responde 404 (pago de otro cliente o inexistente).
         *
         * @param {number|string} id
         * @returns {Promise<Object|null>}
         */
        loadDetail: async function (id) {
            try {
                const res = await window.ApiClient.get('api_portal_pagos.php', { id: id });
                if (res.status === 'success' && res.pago) {
                    return res.pago;
                }
                return null;
            } catch (e) {
                if (e && e.status === 404) return null;
                throw e;
            }
        },

        create: async function (formData) {
            return window.ApiClient.upload('api_portal_pagos.php', formData);
        },

        getById: function (id) {
            return this.cache.find(p => String(p.id) === String(id)) || null;
        },

        /**
         * Descarga el comprobante de un pago (autenticado).
         * No se puede abrir api_portal_comprobante.php como URL estática
         * porque requiere el header X-Session-Token. Se hace fetch + blob
         * y se dispara la descarga con <a download>.
         *
         * @param {number|string} id
         * @returns {Promise<string>} filename descargado
         */
        downloadComprobante: async function (id) {
            const session = window.PortalSession ? window.PortalSession.get() : null;
            if (!session || !session.token) {
                throw new Error('No autenticado.');
            }

            const res = await fetch('api_portal_comprobante.php?id=' + encodeURIComponent(id), {
                method: 'GET',
                headers: { 'X-Session-Token': session.token },
                credentials: 'same-origin'
            });

            if (!res.ok) {
                throw new Error('Comprobante no disponible.');
            }

            const blob = await res.blob();
            let filename = 'comprobante-' + id;
            const cd = res.headers.get('content-disposition') || '';
            const m = cd.match(/filename="?([^";]+)"?/);
            if (m && m[1]) filename = m[1];

            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
            return filename;
        }
    };
})();