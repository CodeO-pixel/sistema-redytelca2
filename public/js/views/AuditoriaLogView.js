// js/views/AuditoriaLogView.js
(function () {
    'use strict';

    function esc(s) {
        return String(s ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatDateTime(val) {
        if (!val) return '—';
        const s = String(val).replace('T', ' ').trim();
        // El backend devuelve "YYYY-MM-DD HH:MM:SS" en UTC (Bootstrap fija
        // time_zone '+00:00' y PHP a UTC). Parseamos como UTC y mostramos
        // en la zona horaria local del navegador del operador.
        // Si el string ya trae zona (Z o +/-HH:MM), no añadimos 'Z'.
        const looksNaive = /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(s);
        const iso = looksNaive ? s.replace(' ', 'T') + 'Z' : s;
        const d = new Date(iso);
        if (isNaN(d.getTime())) {
            return s.slice(0, 19);
        }
        const pad = (n) => String(n).padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} `
             + `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
    }

    function accionBadge(accion) {
        const a = String(accion || '').toUpperCase();
        let cls = 'status-warning';
        if (a === 'INSERT') cls = 'status-ok';
        else if (a === 'UPDATE') cls = 'status-warning';
        else if (a === 'DELETE') cls = 'status-alert';
        else if (a === 'LOGIN') cls = 'status-ok';
        else if (a === 'LOGOUT') cls = 'status-ok';
        else if (a === 'LOGIN_FALLIDO') cls = 'status-alert';
        return `<span class="status ${cls}">${esc(a)}</span>`;
    }

    function actorLabel(log) {
        const tipo = String(log.tipo_actor || '').toLowerCase();
        if (tipo === 'staff') {
            return log.actor_username
                ? esc(log.actor_username)
                : `staff #${esc(log.usuario_id || '?')}`;
        }
        if (tipo === 'cliente') {
            return `cliente #${esc(log.credencial_id || '?')}`;
        }
        return 'sistema';
    }

    function hasValues(log) {
        return (log.old_value != null && String(log.old_value) !== '')
            || (log.new_value != null && String(log.new_value) !== '');
    }

    function prettyJson(raw) {
        if (raw == null || raw === '') return null;
        try {
            return JSON.stringify(JSON.parse(String(raw)), null, 2);
        } catch (e) {
            return String(raw); // JSON inválido: mostrar el raw con aviso
        }
    }

    window.AuditoriaLogView = {
        renderTable: function (logs, hasFilters) {
            const tbody = document.getElementById('auditoria-list');
            if (!tbody) return;

            if (logs.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:24px;">${
                    hasFilters
                        ? `No hay registros que coincidan con los filtros. <a href="#" data-action="clear-filters-empty">Limpiar filtros</a>`
                        : `No hay registros de auditoría en el rango seleccionado.`
                }</td></tr>`;
                const clear = tbody.querySelector('[data-action="clear-filters-empty"]');
                if (clear) clear.addEventListener('click', (ev) => {
                    ev.preventDefault();
                    if (window.AuditoriaLogController) window.AuditoriaLogController.clearFilters();
                });
                return;
            }

            tbody.innerHTML = logs.map(l => {
                const verBtn = hasValues(l)
                    ? `<button type="button" class="table-action btn-ver-audit" data-id="${esc(l.id)}" style="width:auto; padding:4px 8px;">Ver</button>`
                    : `<span class="text-muted" style="font-size:0.85rem;">—</span>`;
                return `
                <tr data-module-row="auditoria" data-id="${esc(l.id)}">
                    <td>${esc(formatDateTime(l.creado_en))}</td>
                    <td>${accionBadge(l.accion)}</td>
                    <td>${esc(l.tabla_afectada || '—')}</td>
                    <td>${esc(l.id_registro_afectado != null ? l.id_registro_afectado : '—')}</td>
                    <td>${actorLabel(l)}</td>
                    <td>${esc(l.ip_address || '—')}</td>
                    <td>${verBtn}</td>
                </tr>
                `;
            }).join('');

            tbody.querySelectorAll('.btn-ver-audit').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const id = btn.dataset.id;
                    if (id && window.AuditoriaLogController) window.AuditoriaLogController.showDetail(id);
                });
            });
        },

        renderPagination: function (page, totalPages) {
            const pag = document.getElementById('auditoria-pagination');
            if (!pag) return;
            pag.innerHTML = `<div class="pagination-controls">
                <button id="btn-auditoria-prev" ${page <= 1 ? 'disabled' : ''}>Anterior</button>
                <span>Página ${page} / ${totalPages}</span>
                <button id="btn-auditoria-next" ${page >= totalPages ? 'disabled' : ''}>Siguiente</button>
            </div>`;
            document.getElementById('btn-auditoria-prev')?.addEventListener('click', () => {
                if (window.AuditoriaLogController) window.AuditoriaLogController.load(Math.max(1, page - 1));
            });
            document.getElementById('btn-auditoria-next')?.addEventListener('click', () => {
                if (window.AuditoriaLogController) window.AuditoriaLogController.load(Math.min(totalPages, page + 1));
            });
        },

        updateCounter: function (total, page, size) {
            const span = document.getElementById('auditoria-counter');
            if (!span) return;
            if (total === 0) { span.textContent = ''; return; }
            const from = (page - 1) * size + 1;
            const to = Math.min(total, page * size);
            span.textContent = `Mostrando ${from}–${to} de ${total}`;
        },

        renderDetailModal: function (log) {
            const m = document.getElementById('auditoria-detail-modal');
            const title = document.getElementById('auditoria-detail-title');
            const content = document.getElementById('auditoria-detail-content');
            if (!m || !content) return;

            if (title) {
                title.textContent = `Detalle — ${log.accion || 'registro'} en ${log.tabla_afectada || '—'} #${log.id_registro_afectado ?? '—'}`;
            }

            const oldJson = prettyJson(log.old_value);
            const newJson = prettyJson(log.new_value);
            const isInvalidOld = oldJson !== null && !oldJson.trim().startsWith('{') && !oldJson.trim().startsWith('[');
            const isInvalidNew = newJson !== null && !newJson.trim().startsWith('{') && !newJson.trim().startsWith('[');

            const block = (label, json, invalid) => `
                <div style="margin-bottom:16px;">
                    <div class="detail-label" style="margin-bottom:6px;">${esc(label)}</div>
                    ${json === null
                        ? `<div class="detail-value" style="color:#94a3b8;">—</div>`
                        : `<pre style="margin:0; padding:12px; background:#0f172a; color:#e2e8f0; border-radius:10px; overflow-x:auto; font-size:0.8rem; line-height:1.4;">${esc(json)}</pre>`
                    }
                    ${invalid ? `<small style="color:#b45309; display:block; margin-top:4px;">JSON inválido: se muestra el valor crudo.</small>` : ''}
                </div>
            `;

            content.innerHTML = `
                <div class="detail-grid" style="margin-bottom:16px;">
                    <div class="detail-card"><span class="detail-label">Fecha</span><div class="detail-value">${esc(formatDateTime(log.creado_en))}</div></div>
                    <div class="detail-card"><span class="detail-label">Actor</span><div class="detail-value">${actorLabel(log)}</div></div>
                    <div class="detail-card"><span class="detail-label">Tipo actor</span><div class="detail-value">${esc(log.tipo_actor || '—')}</div></div>
                    <div class="detail-card"><span class="detail-label">IP</span><div class="detail-value">${esc(log.ip_address || '—')}</div></div>
                </div>
                ${block('Valores anteriores (old_value)', oldJson, isInvalidOld)}
                ${block('Valores nuevos (new_value)', newJson, isInvalidNew)}
            `;

            m.style.display = 'flex';
        },

        _attachListeners: function () {
            // Botón "Aplicar" filtros
            document.querySelectorAll('[data-toolbar="auditoria"] [data-action="apply-filters"]').forEach(b => {
                b.removeAttribute('onclick');
                b.addEventListener('click', () => window.AuditoriaLogController.applyFilters());
            });

            // Botón "Limpiar" filtros
            document.querySelectorAll('[data-toolbar="auditoria"] [data-action="clear-filters"]').forEach(b => {
                b.removeAttribute('onclick');
                b.addEventListener('click', () => window.AuditoriaLogController.clearFilters());
            });

            // Enter en cualquier input/select del toolbar aplica filtros
            document.querySelectorAll('[data-toolbar="auditoria"] input, [data-toolbar="auditoria"] select').forEach(el => {
                el.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        window.AuditoriaLogController.applyFilters();
                    }
                });
            });

            // Modal de detalle: cerrar
            document.querySelectorAll('#auditoria-detail-modal [data-action="close-auditoria-detail"]').forEach(b => {
                b.removeAttribute('onclick');
                b.addEventListener('click', () => {
                    const m = document.getElementById('auditoria-detail-modal');
                    if (m) m.style.display = 'none';
                });
            });

            // Esc en modal
            const m = document.getElementById('auditoria-detail-modal');
            if (m) {
                m.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        e.preventDefault();
                        m.style.display = 'none';
                    }
                });
            }
        }
    };
})();