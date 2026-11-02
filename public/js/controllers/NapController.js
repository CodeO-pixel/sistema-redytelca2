// js/controllers/NapController.js
(function () {
    'use strict';

    window.NapController = {
        init: function () { if (window.NapView) window.NapView._attachListeners(); },
        load: async function (page = 1) {
            if (window.OltModel && window.OltModel.cache.length === 0) {
                await window.OltModel.load();
            }
            await window.NapModel.load();
            if (window.selectedIds) window.selectedIds['naps'] = null;
            document.querySelectorAll('.toolbar-btn[data-module="naps"]').forEach(btn => {
                const action = btn.dataset.action || '';
                if (action === 'edit' || action === 'delete' || action === 'ver-en-mapa') btn.disabled = true;
            });
            this.renderPage(page);
        },
        renderPage: function (page) {
            const term = (document.getElementById('naps-search')?.value || '').toLowerCase();
            const filtered = window.NapModel.cache.filter(n => !term || (n.codigo || '').toLowerCase().includes(term) || (n.ubicacion_fisica || '').toLowerCase().includes(term));
            const size = window.pageSize || 8;
            const totalPages = Math.max(1, Math.ceil(filtered.length / size));
            const safePage = Math.min(Math.max(1, page), totalPages);
            const start = (safePage - 1) * size;
            window.__napCurrentPage = safePage;
            window.NapView.renderTable(filtered.slice(start, start + size));
            window.NapView.renderPagination(safePage, totalPages);
        },
        save: async function () {
            const id = document.getElementById('nap-id').value;
            const wasEditing = !!id;
            const currentPage = window.__napCurrentPage || 1;
            const payload = {
                codigo: document.getElementById('nap-codigo').value.trim(),
                cantidad_puertos_max: document.getElementById('nap-puertos').value,
                ubicacion_fisica: document.getElementById('nap-ubicacion').value.trim(),
                latitud: document.getElementById('nap-latitud').value,
                longitud: document.getElementById('nap-longitud').value,
                olt_id: document.getElementById('nap-olt').value
            };
            if (!payload.codigo || !payload.olt_id) { window.Toast.warning('Faltan datos'); return; }
            try {
                const res = wasEditing ? await window.NapModel.update(id, payload) : await window.NapModel.create(payload);
                if (res.status === 'success') {
                    window.Toast.success(res.message);
                    window.NapView.toggleForm(true);
                    await this.load(wasEditing ? currentPage : 1);
                    if (!wasEditing) {
                        const cache = window.NapModel.cache;
                        const size = window.pageSize || 8;
                        this.renderPage(Math.max(1, Math.ceil(cache.length / size)));
                    }
                } else {
                    window.Toast.error(res.message);
                }
            } catch (e) { window.Toast.error(e.message); }
        },
        edit: async function (id) {
            if (window.NapModel.cache.length === 0) { await window.NapModel.load(); }
            if (window.OltModel && window.OltModel.cache.length === 0) { await window.OltModel.load(); }
            const n = window.NapModel.getById(id);
            if (!n) return;
            window.NapView.toggleForm(false, false);
            window.NapView.fillForm(n);
        },
        confirmDelete: async function (id) {
            const currentPage = window.__napCurrentPage || 1;
            const ok = await window.Modal.confirm('¿Eliminar esta NAP?', { danger: true });
            if (!ok) return;
            try {
                const res = await window.NapModel.delete(id);
                if (res.status === 'success') { window.Toast.success(res.message); await this.load(currentPage); }
                else window.Toast.error(res.message);
            } catch (e) { window.Toast.error(e.message); }
        }
    };

    document.addEventListener('DOMContentLoaded', () => window.NapController.init());
})();