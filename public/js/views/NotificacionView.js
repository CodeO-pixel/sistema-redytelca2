// js/views/NotificacionView.js
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
        const looksNaive = /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(s);
        const iso = looksNaive ? s.replace(' ', 'T') + 'Z' : s;
        const d = new Date(iso);
        if (isNaN(d.getTime())) return s.slice(0, 16);
        const pad = (n) => String(n).padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} `
             + `${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }

    function tipoIcon(tipo) {
        const map = {
            ticket_creado: '🎫',
            ticket_respondido: '💬',
            ticket_cerrado: '✅',
            ticket_respondido_por_cliente: '💬',
            pago_subido: '💰',
            pago_validado: '✅',
            pago_rechazado: '❌',
            factura_emitida: '📄',
            factura_vencida: '⚠️'
        };
        return map[String(tipo || '')] || '🔔';
    }

    window.NotificacionView = {
        renderBadge: function (n) {
            const badge = document.getElementById('notif-bell-badge');
            if (!badge) return;
            const num = Number(n) || 0;
            if (num <= 0) {
                badge.style.display = 'none';
                badge.textContent = '';
            } else {
                badge.style.display = 'inline-flex';
                badge.textContent = num > 99 ? '99+' : String(num);
            }
        },

        renderDropdown: function (items) {
            const list = document.getElementById('notif-dropdown-list');
            if (!list) return;
            if (!items || items.length === 0) {
                list.innerHTML = '<div class="notif-empty">Sin notificaciones.</div>';
                return;
            }
            list.innerHTML = items.map(function (n) {
                const noLeida = n.leida_en == null;
                const cls = noLeida ? 'notif-item notif-unread' : 'notif-item';
                return `<button type="button" class="${cls}" data-notif-id="${esc(n.id)}" data-notif-url="${esc(n.url_destino || '')}">
                    <span class="notif-item-icon">${tipoIcon(n.tipo_evento)}</span>
                    <span class="notif-item-body">
                        <span class="notif-item-title">${esc(n.titulo || '')}</span>
                        <span class="notif-item-msg">${esc(n.mensaje || '')}</span>
                        <span class="notif-item-date">${esc(formatDateTime(n.creado_en))}</span>
                    </span>
                </button>`;
            }).join('');
        },

        renderTable: function (items) {
            const tbody = document.getElementById('notificaciones-list');
            if (!tbody) return;
            if (!items || items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:24px;color:#64748b;">No hay notificaciones.</td></tr>';
                return;
            }
            tbody.innerHTML = items.map(function (n) {
                const noLeida = n.leida_en == null;
                return `<tr data-notif-id="${esc(n.id)}">
                    <td><span class="status ${noLeida ? 'status-warning' : 'status-ok'}">${noLeida ? 'No leída' : 'Leída'}</span></td>
                    <td><strong>${esc(n.titulo || '')}</strong></td>
                    <td>${esc(n.mensaje || '')}</td>
                    <td>${esc(n.tipo_evento || '—')}</td>
                    <td>${esc(formatDateTime(n.creado_en))}</td>
                </tr>`;
            }).join('');
        },

        renderPagination: function (page, totalPages) {
            const pag = document.getElementById('notificaciones-pagination');
            if (!pag) return;
            pag.innerHTML = `<div class="pagination-controls">
                <button id="btn-notif-prev" ${page <= 1 ? 'disabled' : ''}>Anterior</button>
                <span>Página ${page} / ${totalPages}</span>
                <button id="btn-notif-next" ${page >= totalPages ? 'disabled' : ''}>Siguiente</button>
            </div>`;
            document.getElementById('btn-notif-prev')?.addEventListener('click', function () {
                if (window.NotificacionController) window.NotificacionController.load(Math.max(1, page - 1));
            });
            document.getElementById('btn-notif-next')?.addEventListener('click', function () {
                if (window.NotificacionController) window.NotificacionController.load(Math.min(totalPages, page + 1));
            });
        },

        updateCounter: function (total, page, size) {
            const el = document.getElementById('notificaciones-counter');
            if (!el) return;
            if (total === 0) { el.textContent = ''; return; }
            const from = (page - 1) * size + 1;
            const to = Math.min(total, page * size);
            el.textContent = `Mostrando ${from}–${to} de ${total}`;
        },

        toggleDropdown: function (force) {
            const dd = document.getElementById('notif-dropdown');
            if (!dd) return;
            if (typeof force === 'boolean') {
                dd.style.display = force ? 'block' : 'none';
            } else {
                dd.style.display = (dd.style.display === 'block') ? 'none' : 'block';
            }
        },

        _attachListeners: function () {
            const bell = document.getElementById('notif-bell');
            if (bell) {
                bell.removeAttribute('onclick');
                bell.addEventListener('click', function (e) {
                    e.stopPropagation();
                    if (window.NotificacionController) window.NotificacionController.toggleDropdown();
                });
            }

            document.addEventListener('click', function (e) {
                const wrap = document.getElementById('notif-wrap');
                if (!wrap) return;
                if (!wrap.contains(e.target)) {
                    window.NotificacionView.toggleDropdown(false);
                }
            });

            const markAll = document.getElementById('notif-mark-all');
            if (markAll) {
                markAll.removeAttribute('onclick');
                markAll.addEventListener('click', function (e) {
                    e.stopPropagation();
                    if (window.NotificacionController) window.NotificacionController.markAllRead();
                });
            }

            const viewAll = document.getElementById('notif-view-all');
            if (viewAll) {
                viewAll.removeAttribute('onclick');
                viewAll.addEventListener('click', function (e) {
                    e.stopPropagation();
                    window.NotificacionView.toggleDropdown(false);
                    if (window.Router) window.Router.showPage('notificaciones');
                });
            }

            const list = document.getElementById('notif-dropdown-list');
            if (list) {
                list.addEventListener('click', function (e) {
                    const btn = e.target.closest('[data-notif-id]');
                    if (!btn) return;
                    e.stopPropagation();
                    if (window.NotificacionController) window.NotificacionController.markRead(btn.dataset.notifId, btn.dataset.notifUrl || '');
                });
            }

            const search = document.getElementById('notificaciones-search');
            if (search) {
                search.removeAttribute('oninput');
                search.addEventListener('input', function () {
                    clearTimeout(window.NotificacionController._searchTimer);
                    window.NotificacionController._searchTimer = setTimeout(function () {
                        window.NotificacionController.renderPage(1);
                    }, 250);
                });
            }

            const estado = document.getElementById('notificaciones-estado');
            if (estado) {
                estado.addEventListener('change', function () {
                    if (window.NotificacionController) window.NotificacionController.renderPage(1);
                });
            }

            const btnMarkAllPage = document.querySelector('[data-toolbar="notificaciones"] [data-action="mark-all"]');
            if (btnMarkAllPage) {
                btnMarkAllPage.removeAttribute('onclick');
                btnMarkAllPage.addEventListener('click', function () {
                    if (window.NotificacionController) window.NotificacionController.markAllRead();
                });
            }
        }
    };
})();