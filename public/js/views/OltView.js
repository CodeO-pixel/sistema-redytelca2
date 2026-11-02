// js/views/OltView.js
(function () {
    'use strict';
    function esc(s) { return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;'); }
    window.OltView = {
        renderTable: function (olts) {
            const tbody = document.getElementById('olts-list');
            if (!tbody) return;
            tbody.innerHTML = olts.length ? olts.map(o => `
                <tr data-module-row="olts" data-id="${esc(o.id)}">
                    <td><strong>${esc(o.codigo)}</strong></td>
                    <td>${esc(o.marca_modelo)}</td>
                    <td>${esc(o.puertos_pon)}</td>
                    <td>${esc(o.ip_gestion || '—')}</td>
                    <td>${esc(o.nombre_nodo || '—')}</td>
                    <td>${esc(o.total_naps || 0)}</td>
                </tr>
            `).join('') : '<tr><td colspan="6">No hay OLTs.</td></tr>';

            tbody.querySelectorAll('tr[data-id]').forEach(tr => {
                tr.addEventListener('click', () => {
                    const id = tr.getAttribute('data-id');
                    if (window.selectedIds) window.selectedIds['olts'] = id;
                    tbody.querySelectorAll('tr').forEach(r => r.classList.remove('selected-row'));
                    tr.classList.add('selected-row');
                    document.querySelectorAll('.toolbar-btn[data-module="olts"]').forEach(btn => {
                        const action = btn.dataset.action || '';
                        if (action === 'edit' || action === 'delete' || action === 'ver-en-mapa') btn.disabled = false;
                    });
                });
            });
        },
        renderPagination: function (page, totalPages) {
            const pag = document.getElementById('olts-pagination');
            if (!pag) return;
            pag.innerHTML = `<div class="pagination-controls">
                <button id="btn-olts-prev" ${page <= 1 ? 'disabled' : ''}>Anterior</button>
                <span>Página ${page} / ${totalPages}</span>
                <button id="btn-olts-next" ${page >= totalPages ? 'disabled' : ''}>Siguiente</button>
            </div>`;
            document.getElementById('btn-olts-prev')?.addEventListener('click', () => {
                if (window.OltController) window.OltController.renderPage(Math.max(1, page - 1));
            });
            document.getElementById('btn-olts-next')?.addEventListener('click', () => {
                if (window.OltController) window.OltController.renderPage(Math.min(totalPages, page + 1));
            });
        },
        toggleForm: function (forceClose = false, reset = true) {
            const m = document.getElementById('olt-modal');
            if (m) m.style.display = forceClose ? 'none' : (m.style.display === 'flex' ? 'none' : 'flex');
            if (!forceClose && reset) {
                ['olt-id', 'olt-codigo', 'olt-marca', 'olt-puertos', 'olt-ip', 'olt-nodo'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
                this.populateNodo();
            }
        },
        populateNodo: function (selected = '') {
            const sel = document.getElementById('olt-nodo');
            if (!sel) return;
            sel.innerHTML = '<option value="">Selecciona un nodo</option>' + (window.NodoModel?.cache || []).map(n => `<option value="${n.id}">${esc(n.nombre)}</option>`).join('');
            if (selected) sel.value = selected;
        },
        fillForm: function (o) {
            document.getElementById('olt-id').value = o.id || '';
            document.getElementById('olt-codigo').value = o.codigo || '';
            document.getElementById('olt-marca').value = o.marca_modelo || '';
            document.getElementById('olt-puertos').value = o.puertos_pon || 16;
            document.getElementById('olt-ip').value = o.ip_gestion || '';
            this.populateNodo(o.nodo_id || '');
        },
        _attachListeners: function () {
            window.selectedIds.olts = null;
            document.querySelectorAll('.toolbar-btn[data-module="olts"]').forEach(btn => {
                const action = btn.dataset.action || '';
                if (action === 'edit' || action === 'delete' || action === 'ver-en-mapa') btn.disabled = true;
            });

            const btnNew = document.querySelector('.toolbar[data-toolbar="olts"] [data-action="new"]');
            if (btnNew) { btnNew.removeAttribute('onclick'); btnNew.addEventListener('click', () => this.toggleForm()); }
            const btnEdit = document.querySelector('.toolbar[data-toolbar="olts"] [data-action="edit"]');
            if (btnEdit) { btnEdit.removeAttribute('onclick'); btnEdit.addEventListener('click', () => { const id = window.selectedIds?.olts; if (id) window.OltController.edit(id); }); }
            const btnDel = document.querySelector('.toolbar[data-toolbar="olts"] [data-action="delete"]');
            if (btnDel) { btnDel.removeAttribute('onclick'); btnDel.addEventListener('click', () => { const id = window.selectedIds?.olts; if (id) window.OltController.confirmDelete(id); }); }
            const search = document.getElementById('olts-search');
            if (search) { search.removeAttribute('oninput'); search.addEventListener('input', () => window.OltController.renderPage(1)); }
            const btnSave = document.querySelector('#olt-form button[onclick="guardarOlt()"]');
            if (btnSave) { btnSave.removeAttribute('onclick'); btnSave.addEventListener('click', () => window.OltController.save()); }
            const btnCancel = document.querySelector('#olt-form button[onclick="toggleOltForm(true)"]');
            if (btnCancel) { btnCancel.removeAttribute('onclick'); btnCancel.addEventListener('click', () => this.toggleForm(true)); }
            const btnClose = document.querySelector('#olt-modal .modal-close');
            if (btnClose) { btnClose.removeAttribute('onclick'); btnClose.addEventListener('click', () => this.toggleForm(true)); }

            // B3.13 — Botón "Ver en mapa" en el toolbar. La OLT hereda
            // las coordenadas de su nodo (no tiene lat/lon propias).
            const toolbar = document.querySelector('.toolbar[data-toolbar="olts"]');
            if (toolbar && !toolbar.querySelector('[data-action="ver-en-mapa"]')) {
                const btnMap = document.createElement('button');
                btnMap.type = 'button';
                btnMap.className = 'toolbar-btn';
                btnMap.dataset.module = 'olts';
                btnMap.dataset.action = 'ver-en-mapa';
                btnMap.textContent = 'Ver en mapa';
                btnMap.disabled = true;
                btnMap.addEventListener('click', () => {
                    const id = window.selectedIds?.olts;
                    if (!id) return;
                    const olt = window.OltModel && window.OltModel.cache
                        ? window.OltModel.cache.find(o => String(o.id) === String(id))
                        : null;
                    if (!olt) return;
                    if (!window.NodoModel || !window.NodoModel.cache) {
                        window.Toast.warning('No se cargó el cache de nodos.');
                        return;
                    }
                    const nodo = window.NodoModel.cache.find(n => String(n.id) === String(olt.nodo_id));
                    if (!nodo) {
                        window.Toast.warning('La OLT no tiene nodo asignado.');
                        return;
                    }
                    const lat = Number(nodo.latitud);
                    const lon = Number(nodo.longitud);
                    if (!Number.isFinite(lat) || !Number.isFinite(lon) || (lat === 0 && lon === 0)) {
                        window.Toast.warning('El nodo de esta OLT no tiene coordenadas válidas.');
                        return;
                    }
                    if (window.MapaController && window.MapaController.openAt) {
                        window.MapaController.openAt(lat, lon, olt.codigo || olt.marca_modelo, 'olt-' + id);
                    }
                });
                toolbar.appendChild(btnMap);
            }
        }
    };
})();