// js/views/EquipoView.js
(function () {
    'use strict';
    function esc(s) { return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;'); }
    window.EquipoView = {
        renderTable: function (equipos) {
            const tbody = document.getElementById('equipos-list');
            if (!tbody) return;
            tbody.innerHTML = equipos.length ? equipos.map(e => {
                // Jerarquía de dos líneas para no saturar la columna.
                let clienteHtml = '<span class="text-muted">Sin asignar</span>';
                if (e.servicio_id) {
                    const nombre = e.nombres ? `${e.nombres} ${e.apellidos}`.trim() : 'Cliente sin nombre';
                    const cedula = e.cedula ? `C.I. ${e.cedula}` : '';
                    const servicio = e.servicio_alias ? `Servicio: ${e.servicio_alias}` : '';
                    
                    // Línea 1: Nombre. Línea 2: Cédula y Alias del servicio.
                    clienteHtml = `
                        <div style="font-weight: 600; color: var(--text);">${esc(nombre)}</div>
                        <div style="font-size: 0.8rem; color: var(--muted);">
                            ${esc(cedula)}${cedula && servicio ? ' · ' : ''}${esc(servicio)}
                        </div>
                    `;
                }
                
                return `
                <tr data-module-row="equipos" data-id="${esc(e.id)}">
                    <td><strong>${esc(e.tipo)}</strong></td>
                    <td>${esc([e.marca, e.modelo].filter(Boolean).join(' / ') || '—')}</td>
                    <td>${esc(e.direccion_mac)}</td>
                    <td>${esc(e.nap_codigo || '—')}</td>
                    <td>${esc(e.puerto_nap)}</td>
                    <td><span class="status ${e.estado_inventario === 'asignado' ? 'status-ok' : (e.estado_inventario === 'stock' ? 'status-warning' : 'status-alert')}">${esc(e.estado_inventario)}</span></td>
                    <td>${esc(e.propiedad)}</td>
                    <td>${clienteHtml}</td>
                </tr>
            `}).join('') : '<tr><td colspan="8">No hay equipos.</td></tr>';

            tbody.querySelectorAll('tr[data-id]').forEach(tr => {
                tr.addEventListener('click', () => {
                    const id = tr.getAttribute('data-id');
                    const prevId = window.selectedIds ? window.selectedIds['equipos'] : null;
                    if (prevId) {
                        const prevRow = document.querySelector(`[data-module-row="equipos"][data-id="${prevId}"]`);
                        if (prevRow) prevRow.classList.remove('selected-row');
                    }
                    if (window.selectedIds) window.selectedIds['equipos'] = id;
                    tr.classList.add('selected-row');
                    document.querySelectorAll('.toolbar-btn[data-module="equipos"]').forEach(btn => {
                        const action = btn.dataset.action || '';
                        if (action === 'edit' || action === 'delete') btn.disabled = false;
                    });
                });
            });
        },
        renderPagination: function (page, totalPages) {
            const pag = document.getElementById('equipos-pagination');
            if (!pag) return;
            pag.innerHTML = `<div class="pagination-controls">
                <button id="btn-equipos-prev" ${page <= 1 ? 'disabled' : ''}>Anterior</button>
                <span>Página ${page} / ${totalPages}</span>
                <button id="btn-equipos-next" ${page >= totalPages ? 'disabled' : ''}>Siguiente</button>
            </div>`;
            document.getElementById('btn-equipos-prev')?.addEventListener('click', () => {
                if (window.EquipoController) window.EquipoController.renderPage(Math.max(1, page - 1));
            });
            document.getElementById('btn-equipos-next')?.addEventListener('click', () => {
                if (window.EquipoController) window.EquipoController.renderPage(Math.min(totalPages, page + 1));
            });
        },
        toggleForm: function (forceClose = false, reset = true) {
            const m = document.getElementById('equipo-modal');
            if (m) m.style.display = forceClose ? 'none' : (m.style.display === 'flex' ? 'none' : 'flex');
            if (!forceClose && reset) {
                ['equipo-id', 'equipo-marca', 'equipo-modelo', 'equipo-mac', 'equipo-puerto', 'equipo-nap', 'equipo-servicio'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
                const et = document.getElementById('equipo-tipo'); if (et) et.value = 'ONU';
                const ee = document.getElementById('equipo-estado'); if (ee) ee.value = 'stock';
                const ep = document.getElementById('equipo-propiedad'); if (ep) ep.value = 'empresa';
                this.populateSelects();
                this._onEstadoChange();
            }
        },
        populateSelects: function () {
            const nap = document.getElementById('equipo-nap');
            if (nap) {
                nap.innerHTML = '<option value="">Selecciona NAP</option>' + (window.NapModel?.cache || []).map(n => {
                    const label = n.olt_codigo ? `${n.codigo} (${n.olt_codigo})` : n.codigo;
                    return `<option value="${n.id}">${esc(label)}</option>`;
                }).join('');
            }
            const srv = document.getElementById('equipo-servicio');
            if (srv) {
                srv.innerHTML = '<option value="">Sin asignar</option>' + (window.ServicioModel?.cache || []).filter(s => s.estado_comercial === 'activo').map(s => {
                    const label = s.nombres ? `${s.nombres} ${s.apellidos} (${s.alias || 'Servicio'})` : (s.alias || `Servicio #${s.id}`);
                    return `<option value="${s.id}">${esc(label)}</option>`;
                }).join('');
            }
        },
        fillForm: function (e) {
            document.getElementById('equipo-id').value = e.id || '';
            document.getElementById('equipo-tipo').value = e.tipo || 'ONU';
            document.getElementById('equipo-marca').value = e.marca || '';
            document.getElementById('equipo-modelo').value = e.modelo || '';
            document.getElementById('equipo-mac').value = e.direccion_mac || '';
            document.getElementById('equipo-puerto').value = e.puerto_nap || 1;
            document.getElementById('equipo-estado').value = e.estado_inventario || 'stock';
            document.getElementById('equipo-propiedad').value = e.propiedad || 'empresa';
            this.populateSelects();
            document.getElementById('equipo-nap').value = e.nap_id || '';
            document.getElementById('equipo-servicio').value = e.servicio_id || '';
            this._onEstadoChange();
        },
        _onEstadoChange: function () {
            const val = document.getElementById('equipo-estado')?.value;
            const srv = document.getElementById('equipo-servicio');
            if (!srv) return;
            if (val === 'stock' || val === 'devuelto' || val === 'baja') {
                srv.value = '';
                srv.disabled = true;
            } else {
                srv.disabled = false;
            }
        },
        _attachListeners: function () {
            if (window.selectedIds) window.selectedIds['equipos'] = null;
            document.querySelectorAll('.toolbar-btn[data-module="equipos"]').forEach(btn => {
                const action = btn.dataset.action || '';
                if (action === 'edit' || action === 'delete') btn.disabled = true;
            });

            const btnNew = document.querySelector('.toolbar[data-toolbar="equipos"] [data-action="new"]');
            if (btnNew) { btnNew.removeAttribute('onclick'); btnNew.addEventListener('click', () => this.toggleForm()); }
            const btnEdit = document.querySelector('.toolbar[data-toolbar="equipos"] [data-action="edit"]');
            if (btnEdit) { btnEdit.removeAttribute('onclick'); btnEdit.addEventListener('click', () => { const id = window.selectedIds?.equipos; if (id) window.EquipoController.edit(id); }); }
            const btnDel = document.querySelector('.toolbar[data-toolbar="equipos"] [data-action="delete"]');
            if (btnDel) { btnDel.removeAttribute('onclick'); btnDel.addEventListener('click', () => { const id = window.selectedIds?.equipos; if (id) window.EquipoController.confirmDelete(id); }); }
            const search = document.getElementById('equipos-search');
            if (search) { search.removeAttribute('oninput'); search.addEventListener('input', () => window.EquipoController.renderPage(1)); }
            const btnSave = document.querySelector('#equipo-form button[onclick="guardarEquipo()"]');
            if (btnSave) { btnSave.removeAttribute('onclick'); btnSave.addEventListener('click', () => window.EquipoController.save()); }
            const btnCancel = document.querySelector('#equipo-form button[onclick="toggleEquipoForm(true)"]');
            if (btnCancel) { btnCancel.removeAttribute('onclick'); btnCancel.addEventListener('click', () => this.toggleForm(true)); }
            const btnClose = document.querySelector('#equipo-modal .modal-close');
            if (btnClose) { btnClose.removeAttribute('onclick'); btnClose.addEventListener('click', () => this.toggleForm(true)); }

            const estadoSel = document.getElementById('equipo-estado');
            if (estadoSel) {
                estadoSel.removeAttribute('onchange');
                estadoSel.addEventListener('change', () => this._onEstadoChange());
            }
        }
    };
})();