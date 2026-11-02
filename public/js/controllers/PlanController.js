// js/controllers/PlanController.js
(function () {
    'use strict';

    window.PlanController = {
        init: function () {
            if (window.PlanView) window.PlanView._attachListeners();
        },
        load: async function (page = 1) {
            await window.PlanModel.load();
            
            // E1: Resetear selección y deshabilitar botones al recargar
            if (window.selectedIds) window.selectedIds['planes'] = null;
            document.querySelectorAll('.toolbar-btn[data-module="planes"]').forEach(btn => {
                const action = btn.dataset.action || '';
                if (action === 'edit' || action === 'delete') btn.disabled = true;
            });
            
            this.renderPage(page);
        },
        renderPage: function (page) {
            const term = (document.getElementById('planes-search')?.value || '').toLowerCase();
            const filtered = window.PlanModel.cache.filter(p =>
                !term
                || (p.nombre || '').toLowerCase().includes(term)
                || (p.tecnologia || '').toLowerCase().includes(term)
            );
            
            const size = window.pageSize || 8;
            const totalPages = Math.max(1, Math.ceil(filtered.length / size));
            const safePage = Math.min(Math.max(1, page), totalPages);
            const start = (safePage - 1) * size;
            
            // E3: Guardar página actual para el save()
            window.__planCurrentPage = safePage;
            
            window.PlanView.renderTable(filtered.slice(start, start + size));
            window.PlanView.renderPagination(safePage, totalPages);
        },
        save: async function () {
            const id = document.getElementById('plan-id').value;
            const wasEditing = !!id;
            // E4: Capturar página actual
            const currentPage = window.__planCurrentPage || 1;
            
            const payload = {
                nombre: document.getElementById('plan-nombre').value.trim(),
                velocidad_bajada_mbps: document.getElementById('plan-velocidad-bajada').value,
                velocidad_subida_mbps: document.getElementById('plan-velocidad-subida').value,
                tecnologia: document.getElementById('plan-tecnologia').value,
                precio_mensual: document.getElementById('plan-precio').value,
                moneda: document.getElementById('plan-moneda').value.trim() || 'USD'
            };
            
            if (!payload.nombre || !payload.velocidad_bajada_mbps || !payload.precio_mensual) {
                window.Toast.warning('Faltan datos');
                return;
            }
            
            try {
                const res = id
                    ? await window.PlanModel.update(id, payload)
                    : await window.PlanModel.create(payload);
                    
                if (res.status === 'success') {
                    window.Toast.success(res.message);
                    window.PlanView.toggleForm(true);
                    
                    await this.load(wasEditing ? currentPage : 1);
                    
                    // E5: Si es nuevo, navegar a la última página
                    if (!wasEditing) {
                        const cache = window.PlanModel.cache;
                        const size = window.pageSize || 8;
                        this.renderPage(Math.max(1, Math.ceil(cache.length / size)));
                    }
                } else {
                    window.Toast.error(res.message);
                }
            } catch (e) {
                window.Toast.error(e.message || 'Error al guardar');
            }
        },
        edit: async function (id) {
            // E7: Defensivo, recargar cache si está vacío
            if (window.PlanModel.cache.length === 0) {
                await window.PlanModel.load();
            }
            
            const p = window.PlanModel.getById(id);
            if (!p) return;
            
            window.PlanView.toggleForm(false, false);
            window.PlanView.fillForm(p);
        },
        confirmDelete: async function (id) {
            // E4: Capturar página
            const currentPage = window.__planCurrentPage || 1;
            
            let ok = false;
            if (window.Modal) {
                ok = await window.Modal.confirm('¿Eliminar este plan?', { danger: true });
            } else {
                ok = confirm('¿Eliminar este plan?');
            }
            
            if (!ok) return;
            
            try {
                const res = await window.PlanModel.delete(id);
                if (res.status === 'success') {
                    window.Toast.success(res.message);
                    // E4: Volver a la página donde estaba
                    await this.load(currentPage);
                } else {
                    window.Toast.error(res.message);
                }
            } catch (e) {
                window.Toast.error(e.message || 'Error al eliminar');
            }
        }
    };

    document.addEventListener('DOMContentLoaded', () => window.PlanController.init());
})();