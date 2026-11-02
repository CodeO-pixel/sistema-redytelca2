// js/core/helpers.js
// Utilidades globales del frontend. Reemplaza los helpers que vivían en app.js.
// Cargado después de core/* y antes de controllers/*.
(function () {
    'use strict';

    window.escapeHtml = function (unsafe) {
        return String(unsafe ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    };

    window.normalizeForSearch = function (str) {
        return String(str ?? '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    };

    window.getStatusClass = function (status) {
        const s = String(status || '').toLowerCase();
        if (s.includes('complet') || s.includes('cerr') || s === 'activo'
            || s === 'pagada' || s === 'validado' || s === 'vigente' || s === 'operativo') return 'status-ok';
        if (s.includes('curso') || s.includes('proceso') || s === 'pendiente'
            || s === 'suspendido' || s === 'parcial' || s === 'vencido'
            || s === 'mantenimiento' || s === 'stock') return 'status-warning';
        return 'status-alert';
    };

    // Estado global del frontend.
    window.pageSize = 8;
    window.selectedIds = window.selectedIds || {};
    window.currentClienteSearch = window.currentClienteSearch || '';
})();