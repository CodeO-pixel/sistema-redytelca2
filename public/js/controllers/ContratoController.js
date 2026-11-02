// js/controllers/ContratoController.js
(function () {
    'use strict';

    window.ContratoController = {
        _searchTimer: null,
        _dirty: false,

        init: function () {
            if (window.ContratoView) window.ContratoView._attachListeners();
            try {
                const s = sessionStorage.getItem('redytelca.contratos.search');
                const p = sessionStorage.getItem('redytelca.contratos.page');
                const input = document.getElementById('contratos-search');
                if (input && s) input.value = s;
                if (p) window.__contratoCurrentPage = parseInt(p, 10) || 1;
            } catch (e) { /* no-op */ }
        },

        load: async function (page = null) {
            if (window.selectedIds) window.selectedIds['contratos'] = null;
            document.querySelectorAll('.toolbar-btn[data-module="contratos"]').forEach(btn => {
                const action = btn.dataset.action || '';
                if (action === 'edit' || action === 'delete') btn.disabled = true;
            });
            document.querySelectorAll('#contratos-table tbody tr.selected-row').forEach(r => r.classList.remove('selected-row'));

            try {
                if (window.ServicioModel && window.ServicioModel.cache.length === 0) {
                    await window.ServicioModel.load();
                }
                await window.ContratoModel.loadVencer();
                await window.ContratoModel.load();
            } catch (e) {
                if (window.Toast) window.Toast.error(e.message || 'Error al cargar contratos');
                return;
            }

            const target = page != null ? page : (window.__contratoCurrentPage || 1);
            this.renderPage(target);
        },

        renderPage: function (page) {
            const search = this._readSearch();
            const filtered = this._filter(window.ContratoModel.cache, search);
            const size = window.pageSize || 8;
            const totalPages = Math.max(1, Math.ceil(filtered.length / size));
            const safePage = Math.min(Math.max(1, page), totalPages);
            const start = (safePage - 1) * size;
            window.__contratoCurrentPage = safePage;
            this._persist(search, safePage);
            window.ContratoView.renderTable(filtered.slice(start, start + size), search);
            window.ContratoView.renderPagination(safePage, totalPages);
            window.ContratoView.updateCounter(filtered.length, safePage, size);
        },

        save: async function () {
            const id = document.getElementById('contrato-id').value;
            const wasEditing = !!id;
            const currentPage = window.__contratoCurrentPage || 1;

            const payload = {
                id_servicio: document.getElementById('contrato-servicio').value,
                fecha_inicio: document.getElementById('contrato-fecha-inicio').value,
                fecha_fin: document.getElementById('contrato-fecha-fin').value || null,
                tipo_contrato: document.getElementById('contrato-tipo').value,
                estado: document.getElementById('contrato-estado').value,
                observaciones: document.getElementById('contrato-observaciones').value.trim() || null
            };

            if (!payload.id_servicio) { window.Toast.warning('Selecciona un servicio'); return; }
            if (!payload.fecha_inicio) { window.Toast.warning('La fecha de inicio es obligatoria'); return; }
            if (payload.fecha_fin && payload.fecha_fin < payload.fecha_inicio) {
                window.Toast.warning('La fecha de fin no puede ser anterior a la de inicio');
                return;
            }
            if (payload.tipo_contrato !== 'indefinido' && !payload.fecha_fin) {
                window.Toast.warning('Un contrato a plazo fijo o promocional requiere fecha de fin');
                return;
            }

            // Bug 1 (B3.7-hotfix2): 'rescindido' es estado terminal.
            // Defensa de UI: aunque alguien desbloquee el select, no permitir
            // cambiar el estado de un contrato rescindido.
            if (wasEditing) {
                const actual = window.ContratoModel.getById(id);
                if (actual && String(actual.estado).toLowerCase() === 'rescindido' && payload.estado !== 'rescindido') {
                    window.Toast.warning('Un contrato rescindido no puede cambiar de estado. Crea un contrato nuevo si necesitas reactivar el servicio.');
                    return;
                }
            }

            // Bug B mitigation (F2.6 sigue siendo dominio, esto es UI preventiva):
            // no permitir CREAR un contrato para un servicio retirado.
            if (!wasEditing) {
                const srv = window.ServicioModel && window.ServicioModel.cache
                    ? window.ServicioModel.cache.find(s => String(s.id || s.id_servicio) === String(payload.id_servicio))
                    : null;
                if (srv && srv.estado_comercial === 'retirado') {
                    window.Toast.warning('El servicio está retirado. Crea un contrato solo para servicios activos, pendientes o suspendidos.');
                    return;
                }
            }

            const btn = document.querySelector('#contrato-form [data-action="save-contrato"]');
            const originalText = btn ? btn.textContent : '';
            if (btn) { btn.disabled = true; btn.textContent = 'Guardando…'; }

            try {
                const res = wasEditing
                    ? await window.ContratoModel.update(id, payload)
                    : await window.ContratoModel.create(payload);

                if (res.status === 'success') {
                    window.Toast.success(res.message);
                    this._dirty = false;
                    window.ContratoView.toggleForm(true);
                    await this.load(wasEditing ? currentPage : 1);
                    if (!wasEditing) {
                        const cache = window.ContratoModel.cache;
                        const size = window.pageSize || 8;
                        this.renderPage(Math.max(1, Math.ceil(cache.length / size)));
                    }
                } else {
                    window.Toast.error(res.message);
                }
            } catch (e) {
                window.Toast.error(e.message || 'Error al guardar');
            } finally {
                if (btn) { btn.disabled = false; btn.textContent = originalText; }
            }
        },

        edit: async function (id) {
            if (window.ContratoModel.cache.length === 0) await window.ContratoModel.load();
            if (window.ServicioModel && window.ServicioModel.cache.length === 0) await window.ServicioModel.load();
            const c = window.ContratoModel.getById(id);
            if (!c) return;
            window.ContratoView.toggleForm(false, false);
            window.ContratoView.fillForm(c);
        },

        confirmDelete: async function (id) {
            const currentPage = window.__contratoCurrentPage || 1;
            const ok = await window.Modal.confirm('¿Eliminar este contrato?', { danger: true });
            if (!ok) return;
            try {
                const res = await window.ContratoModel.delete(id);
                if (res.status === 'success') {
                    window.Toast.success(res.message);
                    await this.load(currentPage);
                } else {
                    window.Toast.error(res.message);
                }
            } catch (e) {
                window.Toast.error(e.message || 'Error al eliminar');
            }
        },

        exportContratoPdf: function () {
            const draft = document.getElementById('contrato-draft-preview');
            if (!draft || !draft.value.trim()) {
                window.Toast.warning('No hay contenido para exportar');
                return;
            }
            const win = window.open('', '_blank');
            if (!win) {
                window.Toast.error('Pop-up bloqueado. Habilita las ventanas emergentes.');
                return;
            }
            const safe = draft.value
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\n/g, '<br>');
            win.document.write(`<html><head><title>Borrador de contrato</title><style>body{font-family:sans-serif;padding:20px;}</style></head><body><h2>Borrador de contrato</h2>${safe}</body></html>`);
            win.document.close();
            setTimeout(() => win.print(), 250);
        },

        _onSearchCleared: function () {
            clearTimeout(this._searchTimer);
            this.renderPage(1);
        },

        _readSearch: function () {
            const input = document.getElementById('contratos-search');
            return input ? input.value : '';
        },

        _persist: function (search, page) {
            try {
                sessionStorage.setItem('redytelca.contratos.search', search);
                sessionStorage.setItem('redytelca.contratos.page', String(page));
            } catch (e) { /* no-op */ }
        },

        _filter: function (items, search) {
            const s = window.normalizeForSearch(search || '');
            if (!s) return items;
            return items.filter(c => window.normalizeForSearch(
                (c.nombres || '') + ' ' + (c.apellidos || '') + ' ' + (c.cedula || '') + ' ' +
                (c.servicio_alias || '') + ' ' + (c.direccion_texto || '') + ' ' +
                (c.tipo_contrato || '') + ' ' + (c.estado || '')
            ).includes(s));
        }
    };

    document.addEventListener('DOMContentLoaded', () => window.ContratoController.init());
})();