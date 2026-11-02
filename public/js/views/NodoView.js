// js/views/NodoView.js
(function () {
    'use strict';
    function esc(s) { return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;'); }
    window.NodoView = {
        renderTable: function (nodos) {
            const tbody = document.getElementById('nodos-list');
            if (!tbody) return;
            tbody.innerHTML = nodos.length ? nodos.map(n => `
                <tr data-module-row="nodos" data-id="${esc(n.id)}">
                    <td><strong>${esc(n.nombre)}</strong></td>
                    <td>${esc(n.ubicacion || '—')}</td>
                    <td>${esc(n.latitud)}, ${esc(n.longitud)}</td>
                    <td><span class="status ${n.estado === 'activo' ? 'status-ok' : 'status-warning'}">${esc(n.estado)}</span></td>
                    <td>${esc(n.total_olts || 0)}</td>
                </tr>
            `).join('') : '<tr><td colspan="5">No hay nodos.</td></tr>';

            tbody.querySelectorAll('tr[data-id]').forEach(tr => {
                tr.addEventListener('click', () => {
                    const id = tr.getAttribute('data-id');
                    if (window.selectedIds) window.selectedIds['nodos'] = id;
                    tbody.querySelectorAll('tr').forEach(r => r.classList.remove('selected-row'));
                    tr.classList.add('selected-row');
                    document.querySelectorAll('.toolbar-btn[data-module="nodos"]').forEach(btn => {
                        const action = btn.dataset.action || '';
                        if (action === 'edit' || action === 'delete' || action === 'ver-en-mapa') btn.disabled = false;
                    });
                });
            });
        },
        renderPagination: function (page, totalPages) {
            const pag = document.getElementById('nodos-pagination');
            if (!pag) return;
            pag.innerHTML = `<div class="pagination-controls">
                <button id="btn-nodos-prev" ${page <= 1 ? 'disabled' : ''}>Anterior</button>
                <span>Página ${page} / ${totalPages}</span>
                <button id="btn-nodos-next" ${page >= totalPages ? 'disabled' : ''}>Siguiente</button>
            </div>`;
            document.getElementById('btn-nodos-prev')?.addEventListener('click', () => {
                if (window.NodoController) window.NodoController.renderPage(Math.max(1, page - 1));
            });
            document.getElementById('btn-nodos-next')?.addEventListener('click', () => {
                if (window.NodoController) window.NodoController.renderPage(Math.min(totalPages, page + 1));
            });
        },
        toggleForm: function (forceClose = false, reset = true) {
            const m = document.getElementById('nodo-modal');
            if (m) m.style.display = forceClose ? 'none' : (m.style.display === 'flex' ? 'none' : 'flex');
            if (!forceClose && reset) {
                ['nodo-id', 'nodo-nombre', 'nodo-ubicacion', 'nodo-latitud', 'nodo-longitud'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
                const e = document.getElementById('nodo-estado'); if (e) e.value = 'activo';
            }
        },
        fillForm: function (n) {
            document.getElementById('nodo-id').value = n.id || '';
            document.getElementById('nodo-nombre').value = n.nombre || '';
            document.getElementById('nodo-ubicacion').value = n.ubicacion || '';
            document.getElementById('nodo-latitud').value = n.latitud || '';
            document.getElementById('nodo-longitud').value = n.longitud || '';
            document.getElementById('nodo-estado').value = n.estado || 'activo';
        },
        _attachListeners: function () {
            window.selectedIds.nodos = null;
            document.querySelectorAll('.toolbar-btn[data-module="nodos"]').forEach(btn => {
                const action = btn.dataset.action || '';
                if (action === 'edit' || action === 'delete' || action === 'ver-en-mapa') btn.disabled = true;
            });

            const btnNew = document.querySelector('.toolbar[data-toolbar="nodos"] [data-action="new"]');
            if (btnNew) { btnNew.removeAttribute('onclick'); btnNew.addEventListener('click', () => this.toggleForm()); }
            const btnEdit = document.querySelector('.toolbar[data-toolbar="nodos"] [data-action="edit"]');
            if (btnEdit) { btnEdit.removeAttribute('onclick'); btnEdit.addEventListener('click', () => { const id = window.selectedIds?.nodos; if (id) window.NodoController.edit(id); }); }
            const btnDel = document.querySelector('.toolbar[data-toolbar="nodos"] [data-action="delete"]');
            if (btnDel) { btnDel.removeAttribute('onclick'); btnDel.addEventListener('click', () => { const id = window.selectedIds?.nodos; if (id) window.NodoController.confirmDelete(id); }); }
            const search = document.getElementById('nodos-search');
            if (search) { search.removeAttribute('oninput'); search.addEventListener('input', () => window.NodoController.renderPage(1)); }
            const btnSave = document.querySelector('#nodo-form button[onclick="guardarNodo()"]');
            if (btnSave) { btnSave.removeAttribute('onclick'); btnSave.addEventListener('click', () => window.NodoController.save()); }
            const btnCancel = document.querySelector('#nodo-form button[onclick="toggleNodoForm(true)"]');
            if (btnCancel) { btnCancel.removeAttribute('onclick'); btnCancel.addEventListener('click', () => this.toggleForm(true)); }
            const btnClose = document.querySelector('#nodo-modal .modal-close');
            if (btnClose) { btnClose.removeAttribute('onclick'); btnClose.addEventListener('click', () => this.toggleForm(true)); }

            // B3.13 — Botón "Ver en mapa" en el toolbar, junto a Nuevo/Editar/Eliminar.
            const toolbar = document.querySelector('.toolbar[data-toolbar="nodos"]');
            if (toolbar && !toolbar.querySelector('[data-action="ver-en-mapa"]')) {
                const btnMap = document.createElement('button');
                btnMap.type = 'button';
                btnMap.className = 'toolbar-btn';
                btnMap.dataset.module = 'nodos';
                btnMap.dataset.action = 'ver-en-mapa';
                btnMap.textContent = 'Ver en mapa';
                btnMap.disabled = true;
                btnMap.addEventListener('click', () => {
                    const id = window.selectedIds?.nodos;
                    if (!id) return;
                    const nodo = window.NodoModel && window.NodoModel.cache
                        ? window.NodoModel.cache.find(n => String(n.id) === String(id))
                        : null;
                    if (!nodo) return;
                    const lat = Number(nodo.latitud);
                    const lon = Number(nodo.longitud);
                    if (!Number.isFinite(lat) || !Number.isFinite(lon) || (lat === 0 && lon === 0)) {
                        window.Toast.warning('Este nodo no tiene coordenadas válidas.');
                        return;
                    }
                    if (window.MapaController && window.MapaController.openAt) {
                        window.MapaController.openAt(lat, lon, nodo.nombre || 'Nodo', 'nodo-' + id);
                    }
                });
                toolbar.appendChild(btnMap);
            }
        }
    };
})();