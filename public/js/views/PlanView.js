// js/views/PlanView.js
(function () {
    'use strict';

    function esc(s) {
        return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    window.PlanView = {
        renderTable: function (planes) {
            const tbody = document.getElementById('planes-list');
            if (!tbody) return;

            tbody.innerHTML = planes.length ? planes.map(p => {
                const vel = (p.velocidad_bajada_mbps || p.velocidad_subida_mbps)
                    ? `${p.velocidad_bajada_mbps || '?'}/${p.velocidad_subida_mbps || '?'} Mbps`
                    : '—';
                return `
                <tr data-module-row="planes" data-id="${esc(p.id || p.id_plan)}">
                    <td><strong>${esc(p.nombre)}</strong></td>
                    <td>${vel}</td>
                    <td>$${Number(p.precio_mensual).toFixed(2)}</td>
                    <td>${esc(p.moneda)}</td>
                    <td>${esc(p.tecnologia || 'FTTH')}</td>
                    <td>${esc(p.total_servicios || 0)}</td>
                </tr>
            `}).join('') : '<tr><td colspan="6">No hay planes.</td></tr>';

            // E2: Click handler por fila
            tbody.querySelectorAll('tr[data-module-row="planes"]').forEach(tr => {
                tr.addEventListener('click', () => {
                    const id = tr.getAttribute('data-id');
                    
                    // Deseleccionar fila anterior
                    const prevId = window.selectedIds?.planes;
                    if (prevId) {
                        const prev = document.querySelector(`[data-module-row="planes"][data-id="${prevId}"]`);
                        if (prev) prev.classList.remove('selected-row');
                    }
                    
                    // Seleccionar nueva fila
                    if (window.selectedIds) window.selectedIds.planes = id;
                    tr.classList.add('selected-row');
                    
                    // Habilitar botones
                    document.querySelectorAll('.toolbar-btn[data-module="planes"]').forEach(btn => {
                        const action = btn.dataset.action || '';
                        if (action === 'edit' || action === 'delete') btn.disabled = false;
                    });
                });
            });
        },
        renderPagination: function (page, totalPages) {
            const pag = document.getElementById('planes-pagination');
            if (!pag) return;
            
            pag.innerHTML = `<div class="pagination-controls">
                <button id="btn-planes-prev" ${page <= 1 ? 'disabled' : ''}>Anterior</button>
                <span>Página ${page} / ${totalPages}</span>
                <button id="btn-planes-next" ${page >= totalPages ? 'disabled' : ''}>Siguiente</button>
            </div>`;
            
            document.getElementById('btn-planes-prev')?.addEventListener('click', () => {
                if (window.PlanController) window.PlanController.renderPage(Math.max(1, page - 1));
            });
            document.getElementById('btn-planes-next')?.addEventListener('click', () => {
                if (window.PlanController) window.PlanController.renderPage(Math.min(totalPages, page + 1));
            });
        },
        toggleForm: function (forceClose = false, reset = true) {
            const m = document.getElementById('plan-modal');
            if (m) m.style.display = forceClose ? 'none' : (m.style.display === 'flex' ? 'none' : 'flex');
            
            if (!forceClose && reset) {
                ['plan-id', 'plan-nombre', 'plan-velocidad-bajada', 'plan-velocidad-subida', 'plan-precio'].forEach(id => {
                    const el = document.getElementById(id); if (el) el.value = '';
                });
                const elMon = document.getElementById('plan-moneda'); if (elMon) elMon.value = 'USD';
                const elTec = document.getElementById('plan-tecnologia'); if (elTec) elTec.value = 'FTTH';
            }
        },
        fillForm: function (p) {
            document.getElementById('plan-id').value = p.id || p.id_plan || '';
            document.getElementById('plan-nombre').value = p.nombre || '';
            document.getElementById('plan-velocidad-bajada').value = p.velocidad_bajada_mbps || '';
            document.getElementById('plan-velocidad-subida').value = p.velocidad_subida_mbps || '';
            document.getElementById('plan-tecnologia').value = p.tecnologia || 'FTTH';
            document.getElementById('plan-precio').value = p.precio_mensual || '';
            document.getElementById('plan-moneda').value = p.moneda || 'USD';
        },
        _attachListeners: function () {
            // E1: Estado inicial sin selección
            if (window.selectedIds) window.selectedIds.planes = null;
            document.querySelectorAll('.toolbar-btn[data-module="planes"]').forEach(btn => {
                const action = btn.dataset.action || '';
                if (action === 'edit' || action === 'delete') btn.disabled = true;
            });

            const btnNew = document.querySelector('.toolbar[data-toolbar="planes"] [data-action="new"]');
            if (btnNew) {
                btnNew.removeAttribute('onclick');
                btnNew.addEventListener('click', () => this.toggleForm());
                btnNew.disabled = false;
            }

            const btnEdit = document.querySelector('.toolbar[data-toolbar="planes"] [data-action="edit"]');
            if (btnEdit) {
                btnEdit.removeAttribute('onclick');
                btnEdit.addEventListener('click', () => {
                    const id = window.selectedIds?.planes;
                    if (id && window.PlanController) window.PlanController.edit(id);
                });
            }

            const btnDelete = document.querySelector('.toolbar[data-toolbar="planes"] [data-action="delete"]');
            if (btnDelete) {
                btnDelete.removeAttribute('onclick');
                btnDelete.addEventListener('click', () => {
                    const id = window.selectedIds?.planes;
                    if (id && window.PlanController) window.PlanController.confirmDelete(id);
                });
            }

            const search = document.getElementById('planes-search');
            if (search) {
                search.removeAttribute('oninput');
                search.addEventListener('input', () => {
                    if (window.PlanController) window.PlanController.renderPage(1);
                });
            }

            const btnSave = document.querySelector('#plan-form [data-action="save-plan"]');
            if (btnSave) {
                btnSave.removeAttribute('onclick');
                btnSave.addEventListener('click', () => {
                    if (window.PlanController) window.PlanController.save();
                });
            }

            const btnCancel = document.querySelector('#plan-form [data-action="cancel-plan"]');
            if (btnCancel) {
                btnCancel.removeAttribute('onclick');
                btnCancel.addEventListener('click', () => this.toggleForm(true));
            }

            const btnClose = document.querySelector('#plan-modal [data-action="close-plan-modal"]');
            if (btnClose) {
                btnClose.removeAttribute('onclick');
                btnClose.addEventListener('click', () => this.toggleForm(true));
            }
        }
    };
})();