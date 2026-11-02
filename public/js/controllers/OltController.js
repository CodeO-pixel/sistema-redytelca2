// js/controllers/OltController.js
(function () {
    'use strict';

    window.OltController = {
        init: function () { if (window.OltView) window.OltView._attachListeners(); },
        load: async function (page = 1) {
            if (window.NodoModel && window.NodoModel.cache.length === 0) {
                await window.NodoModel.load();
            }
            await window.OltModel.load();
            if (window.selectedIds) window.selectedIds['olts'] = null;
            document.querySelectorAll('.toolbar-btn[data-module="olts"]').forEach(btn => {
                const action = btn.dataset.action || '';
                if (action === 'edit' || action === 'delete' || action === 'ver-en-mapa') btn.disabled = true;
            });
            this.renderPage(page);
        },
        renderPage: function (page) {
            const term = (document.getElementById('olts-search')?.value || '').toLowerCase();
            const filtered = window.OltModel.cache.filter(o => !term || (o.codigo || '').toLowerCase().includes(term) || (o.marca_modelo || '').toLowerCase().includes(term));
            const size = window.pageSize || 8;
            const totalPages = Math.max(1, Math.ceil(filtered.length / size));
            const safePage = Math.min(Math.max(1, page), totalPages);
            const start = (safePage - 1) * size;
            window.__oltCurrentPage = safePage;
            window.OltView.renderTable(filtered.slice(start, start + size));
            window.OltView.renderPagination(safePage, totalPages);
        },
        save: async function () {
            const id = document.getElementById('olt-id').value;
            const wasEditing = !!id;
            const currentPage = window.__oltCurrentPage || 1;
            const payload = {
                codigo: document.getElementById('olt-codigo').value.trim(),
                marca_modelo: document.getElementById('olt-marca').value.trim(),
                puertos_pon: document.getElementById('olt-puertos').value,
                ip_gestion: document.getElementById('olt-ip').value.trim(),
                nodo_id: document.getElementById('olt-nodo').value
            };
            if (!payload.marca_modelo || !payload.nodo_id) { window.Toast.warning('Faltan datos'); return; }
            try {
                const res = wasEditing ? await window.OltModel.update(id, payload) : await window.OltModel.create(payload);
                if (res.status === 'success') {
                    window.Toast.success(res.message);
                    window.OltView.toggleForm(true);
                    await this.load(wasEditing ? currentPage : 1);
                    if (!wasEditing) {
                        const cache = window.OltModel.cache;
                        const size = window.pageSize || 8;
                        this.renderPage(Math.max(1, Math.ceil(cache.length / size)));
                    }
                } else {
                    window.Toast.error(res.message);
                }
            } catch (e) { window.Toast.error(e.message); }
        },
        edit: async function (id) {
            if (window.OltModel.cache.length === 0) { await window.OltModel.load(); }
            if (window.NodoModel && window.NodoModel.cache.length === 0) { await window.NodoModel.load(); }
            const o = window.OltModel.getById(id);
            if (!o) return;
            window.OltView.toggleForm(false, false);
            window.OltView.fillForm(o);
        },
        confirmDelete: async function (id) {
            const currentPage = window.__oltCurrentPage || 1;
            const ok = await window.Modal.confirm('¿Eliminar esta OLT?', { danger: true });
            if (!ok) return;
            try {
                const res = await window.OltModel.delete(id);
                if (res.status === 'success') { window.Toast.success(res.message); await this.load(currentPage); }
                else window.Toast.error(res.message);
            } catch (e) { window.Toast.error(e.message); }
        }
    };

    document.addEventListener('DOMContentLoaded', () => window.OltController.init());
})();