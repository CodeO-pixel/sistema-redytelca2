// js/controllers/EquipoController.js
(function () {
    'use strict';

    window.EquipoController = {
        init: function () { if (window.EquipoView) window.EquipoView._attachListeners(); },
        load: async function (page = 1) {
            // Garantizar NAPs y Servicios cargados (EquipoView.populateSelects).
            if (window.NapModel && window.NapModel.cache.length === 0) {
                await window.NapModel.load();
            }
            if (window.ServicioModel && window.ServicioModel.cache.length === 0) {
                await window.ServicioModel.load();
            }
            await window.EquipoModel.load();
            if (window.selectedIds) window.selectedIds['equipos'] = null;
            document.querySelectorAll('.toolbar-btn[data-module="equipos"]').forEach(btn => {
                const action = btn.dataset.action || '';
                if (action === 'edit' || action === 'delete') btn.disabled = true;
            });
            this.renderPage(page);
        },
        renderPage: function (page) {
            const term = (document.getElementById('equipos-search')?.value || '').toLowerCase();
            const filtered = window.EquipoModel.cache.filter(e =>
                !term
                || (e.direccion_mac || '').toLowerCase().includes(term)
                || (e.tipo || '').toLowerCase().includes(term)
                || (e.nap_codigo || '').toLowerCase().includes(term)
                || (e.nombres || '').toLowerCase().includes(term)
                || (e.apellidos || '').toLowerCase().includes(term)
                || (e.cedula || '').toLowerCase().includes(term)
            );
            const size = window.pageSize || 8;
            const totalPages = Math.max(1, Math.ceil(filtered.length / size));
            const safePage = Math.min(Math.max(1, page), totalPages);
            const start = (safePage - 1) * size;
            window.__equipoCurrentPage = safePage;
            window.EquipoView.renderTable(filtered.slice(start, start + size));
            window.EquipoView.renderPagination(safePage, totalPages);
        },
        save: async function () {
            const id = document.getElementById('equipo-id').value;
            const wasEditing = !!id;
            const currentPage = window.__equipoCurrentPage || 1;

            const payload = {
                tipo: document.getElementById('equipo-tipo').value,
                marca: document.getElementById('equipo-marca').value.trim(),
                modelo: document.getElementById('equipo-modelo').value.trim(),
                direccion_mac: document.getElementById('equipo-mac').value.trim().toUpperCase(),
                puerto_nap: document.getElementById('equipo-puerto').value,
                estado_inventario: document.getElementById('equipo-estado').value,
                propiedad: document.getElementById('equipo-propiedad').value,
                nap_id: document.getElementById('equipo-nap').value,
                servicio_id: document.getElementById('equipo-servicio').value || null
            };

            if (!payload.direccion_mac || !payload.puerto_nap || !payload.nap_id) {
                window.Toast.warning('Faltan datos');
                return;
            }

            // Coherencia estado ↔ servicio (refleja EquipoService::validarCoherencia).
            if (payload.estado_inventario === 'asignado' && !payload.servicio_id) {
                window.Toast.warning('Un equipo asignado requiere un servicio.');
                return;
            }
            if (payload.estado_inventario === 'stock' && payload.servicio_id) {
                window.Toast.warning('Un equipo en stock no puede tener un servicio.');
                return;
            }

            try {
                const res = wasEditing ? await window.EquipoModel.update(id, payload) : await window.EquipoModel.create(payload);
                if (res.status === 'success') {
                    window.Toast.success(res.message);
                    window.EquipoView.toggleForm(true);
                    await this.load(wasEditing ? currentPage : 1);
                    if (!wasEditing) {
                        const cache = window.EquipoModel.cache;
                        const size = window.pageSize || 8;
                        this.renderPage(Math.max(1, Math.ceil(cache.length / size)));
                    }
                } else {
                    window.Toast.error(res.message);
                }
            } catch (e) { window.Toast.error(e.message); }
        },
        edit: async function (id) {
            if (window.EquipoModel.cache.length === 0) { await window.EquipoModel.load(); }
            if (window.NapModel && window.NapModel.cache.length === 0) { await window.NapModel.load(); }
            if (window.ServicioModel && window.ServicioModel.cache.length === 0) { await window.ServicioModel.load(); }
            const e = window.EquipoModel.getById(id);
            if (!e) return;
            window.EquipoView.toggleForm(false, false);
            window.EquipoView.fillForm(e);
        },
        confirmDelete: async function (id) {
            const currentPage = window.__equipoCurrentPage || 1;
            const ok = await window.Modal.confirm('¿Eliminar este equipo?', { danger: true });
            if (!ok) return;
            try {
                const res = await window.EquipoModel.delete(id);
                if (res.status === 'success') { window.Toast.success(res.message); await this.load(currentPage); }
                else window.Toast.error(res.message);
            } catch (e) { window.Toast.error(e.message); }
        }
    };

    document.addEventListener('DOMContentLoaded', () => window.EquipoController.init());
})();