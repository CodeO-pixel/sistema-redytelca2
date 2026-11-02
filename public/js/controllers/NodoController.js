// js/controllers/NodoController.js
(function () {
    'use strict';

    window.NodoController = {
        init: function () { if (window.NodoView) window.NodoView._attachListeners(); },
        load: async function (page = 1) {
            await window.NodoModel.load();
            if (window.selectedIds) window.selectedIds['nodos'] = null;
            document.querySelectorAll('.toolbar-btn[data-module="nodos"]').forEach(btn => {
                const action = btn.dataset.action || '';
                if (action === 'edit' || action === 'delete' || action === 'ver-en-mapa') btn.disabled = true;
            });
            this.renderPage(page);
        },
        renderPage: function (page) {
            const term = (document.getElementById('nodos-search')?.value || '').toLowerCase();
            const filtered = window.NodoModel.cache.filter(n => !term || (n.nombre || '').toLowerCase().includes(term));
            const size = window.pageSize || 8;
            const totalPages = Math.max(1, Math.ceil(filtered.length / size));
            const safePage = Math.min(Math.max(1, page), totalPages);
            const start = (safePage - 1) * size;
            window.__nodoCurrentPage = safePage;
            window.NodoView.renderTable(filtered.slice(start, start + size));
            window.NodoView.renderPagination(safePage, totalPages);
        },
        save: async function () {
            const id = document.getElementById('nodo-id').value;
            const wasEditing = !!id;
            const currentPage = window.__nodoCurrentPage || 1;
            const payload = {
                nombre: document.getElementById('nodo-nombre').value.trim(),
                ubicacion: document.getElementById('nodo-ubicacion').value.trim(),
                latitud: document.getElementById('nodo-latitud').value,
                longitud: document.getElementById('nodo-longitud').value,
                estado: document.getElementById('nodo-estado').value
            };
            if (!payload.nombre || !payload.latitud || !payload.longitud) { window.Toast.warning('Faltan datos'); return; }
            try {
                const res = wasEditing ? await window.NodoModel.update(id, payload) : await window.NodoModel.create(payload);
                if (res.status === 'success') {
                    window.Toast.success(res.message);
                    window.NodoView.toggleForm(true);
                    await this.load(wasEditing ? currentPage : 1);
                    if (!wasEditing) {
                        const cache = window.NodoModel.cache;
                        const size = window.pageSize || 8;
                        this.renderPage(Math.max(1, Math.ceil(cache.length / size)));
                    }
                } else {
                    window.Toast.error(res.message);
                }
            } catch (e) { window.Toast.error(e.message); }
        },
        edit: async function (id) {
            if (window.NodoModel.cache.length === 0) { await window.NodoModel.load(); }
            const n = window.NodoModel.getById(id);
            if (!n) return;
            window.NodoView.toggleForm(false, false);
            window.NodoView.fillForm(n);
        },
        confirmDelete: async function (id) {
            const currentPage = window.__nodoCurrentPage || 1;
            const ok = await window.Modal.confirm('¿Eliminar este nodo?', { danger: true });
            if (!ok) return;
            try {
                const res = await window.NodoModel.delete(id);
                if (res.status === 'success') { window.Toast.success(res.message); await this.load(currentPage); }
                else window.Toast.error(res.message);
            } catch (e) { window.Toast.error(e.message); }
        }
    };

    document.addEventListener('DOMContentLoaded', () => window.NodoController.init());
})();