// js/views/NapView.js
(function () {
    'use strict';
    function esc(s) { return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;'); }
    window.NapView = {
        renderTable: function (naps) {
            const tbody = document.getElementById('naps-list');
            if (!tbody) return;
            tbody.innerHTML = naps.length ? naps.map(n => `
                <tr data-module-row="naps" data-id="${esc(n.id)}">
                    <td><strong>${esc(n.codigo)}</strong></td>
                    <td>${esc(n.cantidad_puertos_max)}</td>
                    <td>${esc(n.ubicacion_fisica || '—')}</td>
                    <td>${esc(n.olt_codigo || '—')}</td>
                    <td>${esc(n.nodo_nombre || '—')}</td>
                    <td>${esc(n.total_clientes || 0)}</td>
                </tr>
            `).join('') : '<tr><td colspan="6">No hay NAPs.</td></tr>';

            tbody.querySelectorAll('tr[data-id]').forEach(tr => {
                tr.addEventListener('click', () => {
                    const id = tr.getAttribute('data-id');
                    if (window.selectedIds) window.selectedIds['naps'] = id;
                    tbody.querySelectorAll('tr').forEach(r => r.classList.remove('selected-row'));
                    tr.classList.add('selected-row');
                    document.querySelectorAll('.toolbar-btn[data-module="naps"]').forEach(btn => {
                        const action = btn.dataset.action || '';
                        if (action === 'edit' || action === 'delete' || action === 'ver-en-mapa') btn.disabled = false;
                    });
                });
            });
        },
        renderPagination: function (page, totalPages) {
            const pag = document.getElementById('naps-pagination');
            if (!pag) return;
            pag.innerHTML = `<div class="pagination-controls">
                <button id="btn-naps-prev" ${page <= 1 ? 'disabled' : ''}>Anterior</button>
                <span>Página ${page} / ${totalPages}</span>
                <button id="btn-naps-next" ${page >= totalPages ? 'disabled' : ''}>Siguiente</button>
            </div>`;
            document.getElementById('btn-naps-prev')?.addEventListener('click', () => {
                if (window.NapController) window.NapController.renderPage(Math.max(1, page - 1));
            });
            document.getElementById('btn-naps-next')?.addEventListener('click', () => {
                if (window.NapController) window.NapController.renderPage(Math.min(totalPages, page + 1));
            });
        },
        toggleForm: function (forceClose = false, reset = true) {
            const m = document.getElementById('nap-modal');
            if (m) m.style.display = forceClose ? 'none' : (m.style.display === 'flex' ? 'none' : 'flex');
            if (!forceClose && reset) {
                ['nap-id', 'nap-codigo', 'nap-puertos', 'nap-ubicacion', 'nap-latitud', 'nap-longitud', 'nap-olt'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
                this.populateOlt();
            }
        },
        populateOlt: function (selected = '') {
            const sel = document.getElementById('nap-olt');
            if (!sel) return;
            sel.innerHTML = '<option value="">Selecciona una OLT</option>' + (window.OltModel?.cache || []).map(o => `<option value="${o.id}">${esc(o.codigo || o.marca_modelo)}</option>`).join('');
            if (selected) sel.value = selected;
        },
        fillForm: function (n) {
            document.getElementById('nap-id').value = n.id || '';
            document.getElementById('nap-codigo').value = n.codigo || '';
            document.getElementById('nap-puertos').value = n.cantidad_puertos_max || 16;
            document.getElementById('nap-ubicacion').value = n.ubicacion_fisica || '';
            document.getElementById('nap-latitud').value = n.latitud || '';
            document.getElementById('nap-longitud').value = n.longitud || '';
            this.populateOlt(n.olt_id || '');
        },
        _attachListeners: function () {
            window.selectedIds.naps = null;
            document.querySelectorAll('.toolbar-btn[data-module="naps"]').forEach(btn => {
                const action = btn.dataset.action || '';
                if (action === 'edit' || action === 'delete' || action === 'ver-en-mapa') btn.disabled = true;
            });

            const btnNew = document.querySelector('.toolbar[data-toolbar="naps"] [data-action="new"]');
            if (btnNew) { btnNew.removeAttribute('onclick'); btnNew.addEventListener('click', () => this.toggleForm()); }
            const btnEdit = document.querySelector('.toolbar[data-toolbar="naps"] [data-action="edit"]');
            if (btnEdit) { btnEdit.removeAttribute('onclick'); btnEdit.addEventListener('click', () => { const id = window.selectedIds?.naps; if (id) window.NapController.edit(id); }); }
            const btnDel = document.querySelector('.toolbar[data-toolbar="naps"] [data-action="delete"]');
            if (btnDel) { btnDel.removeAttribute('onclick'); btnDel.addEventListener('click', () => { const id = window.selectedIds?.naps; if (id) window.NapController.confirmDelete(id); }); }
            const search = document.getElementById('naps-search');
            if (search) { search.removeAttribute('oninput'); search.addEventListener('input', () => window.NapController.renderPage(1)); }
            const btnSave = document.querySelector('#nap-form button[onclick="guardarNap()"]');
            if (btnSave) { btnSave.removeAttribute('onclick'); btnSave.addEventListener('click', () => window.NapController.save()); }
            const btnCancel = document.querySelector('#nap-form button[onclick="toggleNapForm(true)"]');
            if (btnCancel) { btnCancel.removeAttribute('onclick'); btnCancel.addEventListener('click', () => this.toggleForm(true)); }
            const btnClose = document.querySelector('#nap-modal .modal-close');
            if (btnClose) { btnClose.removeAttribute('onclick'); btnClose.addEventListener('click', () => this.toggleForm(true)); }

            // B3.13 — Botón "Ver en mapa" en el toolbar, junto a Nuevo/Editar/Eliminar.
            const toolbar = document.querySelector('.toolbar[data-toolbar="naps"]');
            if (toolbar && !toolbar.querySelector('[data-action="ver-en-mapa"]')) {
                const btnMap = document.createElement('button');
                btnMap.type = 'button';
                btnMap.className = 'toolbar-btn';
                btnMap.dataset.module = 'naps';
                btnMap.dataset.action = 'ver-en-mapa';
                btnMap.textContent = 'Ver en mapa';
                btnMap.disabled = true;
                btnMap.addEventListener('click', () => {
                    const id = window.selectedIds?.naps;
                    if (!id) return;
                    const nap = window.NapModel && window.NapModel.cache
                        ? window.NapModel.cache.find(n => String(n.id) === String(id))
                        : null;
                    if (!nap) return;
                    const lat = Number(nap.latitud);
                    const lon = Number(nap.longitud);
                    if (!Number.isFinite(lat) || !Number.isFinite(lon) || (lat === 0 && lon === 0)) {
                        window.Toast.warning('Esta NAP no tiene coordenadas válidas.');
                        return;
                    }
                    if (window.MapaController && window.MapaController.openAt) {
                        window.MapaController.openAt(lat, lon, nap.codigo || 'NAP', 'nap-' + id);
                    }
                });
                toolbar.appendChild(btnMap);
            }
        }
    };
})();