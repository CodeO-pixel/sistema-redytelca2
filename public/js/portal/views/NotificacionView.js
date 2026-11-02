// js/portal/views/NotificacionView.js
// View del portal para notificaciones (086d).
//
// Renderiza:
//   - Badge de la campana (número de no leídas).
//   - Dropdown con últimas 20 (preview).
//   - Tabla de la página dedicada `portal-notificaciones` (buzón completo).
//   - Modal de detalle.
//
// Patrón Sonar: campana en topbar, badge rojo, dropdown con lista, click
// en item marca como leída + navega a url_destino. Digitel/Inter
// (Venezuela): buzón de notificaciones en el portal de autogestión.
//
// FIX 4BUGS + PORTAL-NOTIF-PAGE (2026-11-02):
//   - P2: restaurado el listener de `#portal-notif-view-all`. Ahora navega
//     a `portal-notificaciones` (página dedicada) y cierra el dropdown.
//     La página la registra main.js en PortalRouter y la alimenta
//     PortalNotificacionController.load(1).
//   - Añadidos `renderTable`, `renderPagination`, `updateCounter` para la
//     página dedicada.
//   - Añadidos listeners de la página dedicada (delegation en tbody):
//     input búsqueda con debounce, select estado, botón limpiar,
//     botón marcar todas, click en fila.
//
// Decisión de patrón: el click en una fila de la tabla usa DELEGATION
// sobre el tbody (`tbody.addEventListener('click', ...)`), no per-row
// como otros módulos del portal. Ventaja: sobrevive a re-renders
// (`renderTable` se puede llamar N veces sin re-wiring). Mismo patrón
// que el listener del dropdown (`#portal-notif-dropdown-list`). No
// replica el bug del admin (`NotificacionView` del staff), cuyo tbody
// no tiene listener y por eso las filas del buzón admin no son
// clickables.
//
// Los timestamps del backend vienen en UTC. Se convierten a local
// con formatDateTimeLocal (mismo patrón que el admin, VERIFICACION.md §2.32).
(function () {
    'use strict';

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /**
     * Convierte un timestamp UTC naive ('YYYY-MM-DD HH:MM:SS') a hora
     * local del navegador (VET, UTC-4).
     */
    function formatDateTimeLocal(val) {
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

    /**
     * Tiempo relativo estilo Sonar ("hace 5 min", "hace 2 h").
     * Usado en el dropdown (no en la tabla, que muestra fecha absoluta).
     */
    function timeAgo(val) {
        if (!val) return '';
        const s = String(val).replace('T', ' ').trim();
        const looksNaive = /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(s);
        const iso = looksNaive ? s.replace(' ', 'T') + 'Z' : s;
        const d = new Date(iso);
        if (isNaN(d.getTime())) return '';
        const diffMs = Date.now() - d.getTime();
        const min = Math.floor(diffMs / 60000);
        if (min < 1) return 'ahora';
        if (min < 60) return `hace ${min} min`;
        const h = Math.floor(min / 60);
        if (h < 24) return `hace ${h} h`;
        const dias = Math.floor(h / 24);
        return `hace ${dias} d`;
    }

    /**
     * Icono según tipo_evento. Mismos emojis que el admin para
     * consistencia visual.
     */
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

    function stateClass(n) {
        return n.leida_en == null ? 'portal-status-warning' : 'portal-status-ok';
    }

    function stateLabel(n) {
        return n.leida_en == null ? 'No leída' : 'Leída';
    }

    window.PortalNotificacionView = {
        // ─────────────────────────────────────────────────────────────
        // Badge (topbar)
        // ─────────────────────────────────────────────────────────────

        /**
         * Pinta el badge de la campana con el número de no leídas.
         * Si n <= 0, oculta el badge.
         *
         * @param {number} n
         */
        renderBadge: function (n) {
            const badge = document.getElementById('portal-notif-bell-badge');
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

        // ─────────────────────────────────────────────────────────────
        // Dropdown (preview de 20)
        // ─────────────────────────────────────────────────────────────

        /**
         * Pinta el dropdown con las últimas notificaciones.
         *
         * @param {Array} items Hasta 20 (el controller ya slicea).
         */
        renderDropdown: function (items) {
            const list = document.getElementById('portal-notif-dropdown-list');
            if (!list) return;
            if (!items || items.length === 0) {
                list.innerHTML = '<div class="portal-notif-empty">Sin notificaciones.</div>';
                return;
            }
            list.innerHTML = items.map(function (n) {
                const noLeida = n.leida_en == null;
                const cls = noLeida ? 'portal-notif-item portal-notif-unread' : 'portal-notif-item';
                return `<button type="button" class="${cls}"
                    data-notif-id="${esc(n.id)}"
                    data-notif-url="${esc(n.url_destino || '')}">
                    <span class="portal-notif-item-icon">${tipoIcon(n.tipo_evento)}</span>
                    <span class="portal-notif-item-body">
                        <span class="portal-notif-item-title">${esc(n.titulo || '')}</span>
                        <span class="portal-notif-item-msg">${esc(n.mensaje || '')}</span>
                        <span class="portal-notif-item-date">${esc(timeAgo(n.fecha_envio || n.creado_en))}</span>
                    </span>
                </button>`;
            }).join('');
        },

        /**
         * Muestra u oculta el dropdown.
         *
         * @param {boolean|undefined} force - true=mostrar, false=ocultar, undefined=toggle
         */
        toggleDropdown: function (force) {
            const dd = document.getElementById('portal-notif-dropdown');
            if (!dd) return;
            if (typeof force === 'boolean') {
                dd.style.display = force ? 'block' : 'none';
                const bell = document.getElementById('portal-notif-bell');
                if (bell) bell.setAttribute('aria-expanded', force ? 'true' : 'false');
            } else {
                const isOpen = dd.style.display === 'block';
                dd.style.display = isOpen ? 'none' : 'block';
                const bell = document.getElementById('portal-notif-bell');
                if (bell) bell.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
            }
        },

        // ─────────────────────────────────────────────────────────────
        // Tabla de la página dedicada `portal-notificaciones`
        // ─────────────────────────────────────────────────────────────

        /**
         * Pinta la tabla del buzón completo.
         * Columnas: Estado | Título | Mensaje | Tipo | Fecha | Acciones.
         *
         * @param {Array} items Página actual del buzón (≤50).
         */
        renderTable: function (items) {
            const tbody = document.getElementById('portal-notificaciones-list');
            if (!tbody) return;

            if (!items || items.length === 0) {
                const searchEl = document.getElementById('portal-notif-search');
                const estadoEl = document.getElementById('portal-notif-estado');
                const hasFilters = (searchEl && searchEl.value)
                    || (estadoEl && estadoEl.value);
                tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:24px;color:var(--muted);">${
                    hasFilters
                        ? 'Sin resultados para los filtros aplicados. <a href="#" data-action="clear-filters-empty" style="color:var(--accent);">Limpiar filtros</a>'
                        : 'No tienes notificaciones.'
                }</td></tr>`;
                const clear = tbody.querySelector('[data-action="clear-filters-empty"]');
                if (clear) {
                    clear.addEventListener('click', function (ev) {
                        ev.preventDefault();
                        if (window.PortalNotificacionController) {
                            window.PortalNotificacionController.clearFilters();
                        }
                    });
                }
                return;
            }

            tbody.innerHTML = items.map(function (n) {
                const estadoBadge = `<span class="portal-status ${stateClass(n)}">${esc(stateLabel(n))}</span>`;
                return `<tr data-notif-row-id="${esc(n.id)}" data-notif-url="${esc(n.url_destino || '')}">
                    <td>${estadoBadge}</td>
                    <td><strong>${esc(n.titulo || '')}</strong></td>
                    <td>${esc(n.mensaje || '')}</td>
                    <td>${tipoIcon(n.tipo_evento)} ${esc(n.tipo_evento || '—')}</td>
                    <td>${esc(formatDateTimeLocal(n.fecha_envio || n.creado_en))}</td>
                    <td><button type="button" class="portal-btn-secondary" data-action="view-notif" style="padding:5px 10px;font-size:0.8rem;">Ver</button></td>
                </tr>`;
            }).join('');
        },

        renderPagination: function (page, totalPages) {
            const pag = document.getElementById('portal-notificaciones-pagination');
            if (!pag) return;
            if (totalPages <= 1) {
                pag.innerHTML = '';
                return;
            }
            pag.innerHTML = `
                <div class="pagination-controls">
                    <button type="button" id="btn-portal-notif-prev" ${page <= 1 ? 'disabled' : ''}>Anterior</button>
                    <span class="pagination-info">Página ${page} / ${totalPages}</span>
                    <button type="button" id="btn-portal-notif-next" ${page >= totalPages ? 'disabled' : ''}>Siguiente</button>
                </div>`;
            document.getElementById('btn-portal-notif-prev')?.addEventListener('click', () => {
                if (window.PortalNotificacionController) {
                    window.PortalNotificacionController.renderPage(Math.max(1, page - 1));
                }
            });
            document.getElementById('btn-portal-notif-next')?.addEventListener('click', () => {
                if (window.PortalNotificacionController) {
                    window.PortalNotificacionController.renderPage(Math.min(totalPages, page + 1));
                }
            });
        },

        updateCounter: function (total, page, size) {
            const span = document.getElementById('portal-notificaciones-counter');
            if (!span) return;
            if (total === 0) {
                span.textContent = '';
                return;
            }
            const from = (page - 1) * size + 1;
            const to = Math.min(total, page * size);
            span.textContent = `Mostrando ${from}–${to} de ${total}`;
        },

        // ─────────────────────────────────────────────────────────────
        // Modal de detalle
        // ─────────────────────────────────────────────────────────────

        /**
         * Pinta el modal de detalle de una notificación.
         *
         * @param {Object} n
         */
        renderDetailModal: function (n) {
            const m = document.getElementById('portal-notif-detail-modal');
            const title = document.getElementById('portal-notif-detail-title');
            const content = document.getElementById('portal-notif-detail-content');
            if (!m || !content) return;

            if (title) title.textContent = n.titulo || 'Notificación';

            content.innerHTML = `
                <div class="portal-detail-grid">
                    <div class="portal-detail-card" style="grid-column: 1 / -1;">
                        <span class="portal-detail-label">Mensaje</span>
                        <div class="portal-detail-value" style="white-space: pre-wrap;">${esc(n.mensaje || '—')}</div>
                    </div>
                    <div class="portal-detail-card">
                        <span class="portal-detail-label">Tipo</span>
                        <div class="portal-detail-value">${esc(n.tipo_evento || '—')}</div>
                    </div>
                    <div class="portal-detail-card">
                        <span class="portal-detail-label">Fecha</span>
                        <div class="portal-detail-value">${esc(formatDateTimeLocal(n.fecha_envio || n.creado_en))}</div>
                    </div>
                    <div class="portal-detail-card">
                        <span class="portal-detail-label">Estado</span>
                        <div class="portal-detail-value">
                            <span class="portal-status ${stateClass(n)}">${esc(stateLabel(n))}</span>
                        </div>
                    </div>
                </div>
                ${n.url_destino ? `
                <div class="portal-modal-actions" style="margin-top:16px; justify-content:flex-end;">
                    <button type="button" class="portal-btn-primary" data-action="notif-ir">
                        Ir al detalle
                    </button>
                </div>` : ''}
            `;

            const btnIr = content.querySelector('[data-action="notif-ir"]');
            if (btnIr) {
                btnIr.addEventListener('click', function () {
                    if (window.PortalNotificacionController) {
                        window.PortalNotificacionController.navigateTo(n.url_destino);
                    }
                    const mm = document.getElementById('portal-notif-detail-modal');
                    if (mm) mm.style.display = 'none';
                });
            }

            m.style.display = 'flex';
        },

        closeDetailModal: function () {
            const m = document.getElementById('portal-notif-detail-modal');
            if (m) m.style.display = 'none';
        },

        // ─────────────────────────────────────────────────────────────
        // Listeners (se atan una sola vez en init())
        // ─────────────────────────────────────────────────────────────

        /**
         * Listeners de la campana, dropdown, tabla de la página dedicada
         * y modales. Se llama una vez desde el controller init().
         */
        _attachListeners: function () {
            // ── Campana (topbar) ──
            const bell = document.getElementById('portal-notif-bell');
            if (bell) {
                bell.addEventListener('click', function (e) {
                    e.stopPropagation();
                    if (window.PortalNotificacionController) {
                        window.PortalNotificacionController.toggleDropdown();
                    }
                });
            }

            // Click fuera del dropdown lo cierra
            document.addEventListener('click', function (e) {
                const wrap = document.getElementById('portal-notif-wrap');
                if (!wrap) return;
                if (!wrap.contains(e.target)) {
                    if (window.PortalNotificacionView) {
                        window.PortalNotificacionView.toggleDropdown(false);
                    }
                }
            });

            // "Marcar todas como leídas" en el header del dropdown
            const markAllDrop = document.getElementById('portal-notif-mark-all');
            if (markAllDrop) {
                markAllDrop.addEventListener('click', function (e) {
                    e.stopPropagation();
                    if (window.PortalNotificacionController) {
                        window.PortalNotificacionController.markAllRead();
                    }
                });
            }

            // "Ver todas las notificaciones" en el footer del dropdown
            // → navega a la página dedicada `portal-notificaciones`.
            // El PortalRouter tiene registrada esa página con onShow:
            // load(1). No hace falta llamar load() aquí; showPage lo
            // dispara. toggleDropdown(false) cierra el dropdown antes.
            const viewAll = document.getElementById('portal-notif-view-all');
            if (viewAll) {
                viewAll.addEventListener('click', function (e) {
                    e.stopPropagation();
                    if (window.PortalNotificacionView) {
                        window.PortalNotificacionView.toggleDropdown(false);
                    }
                    if (window.PortalRouter) {
                        window.PortalRouter.showPage('portal-notificaciones');
                    }
                });
            }

            // Click en item del dropdown (delegation sobre la lista)
            const dropdownList = document.getElementById('portal-notif-dropdown-list');
            if (dropdownList) {
                dropdownList.addEventListener('click', function (e) {
                    const btn = e.target.closest('[data-notif-id]');
                    if (!btn) return;
                    e.stopPropagation();
                    if (window.PortalNotificacionController) {
                        window.PortalNotificacionController.markRead(
                            btn.dataset.notifId,
                            btn.dataset.notifUrl || ''
                        );
                    }
                });
            }

            // ── Página dedicada `portal-notificaciones` ──

            // Búsqueda con debounce (250ms)
            const searchInput = document.getElementById('portal-notif-search');
            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    clearTimeout(window.PortalNotificacionController._searchTimer);
                    window.PortalNotificacionController._searchTimer = setTimeout(function () {
                        if (window.PortalNotificacionController) {
                            window.PortalNotificacionController.applyFilters();
                        }
                    }, 250);
                });
                searchInput.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        clearTimeout(window.PortalNotificacionController._searchTimer);
                        if (window.PortalNotificacionController) {
                            window.PortalNotificacionController.applyFilters();
                        }
                    }
                });
            }

            // Select de estado: aplica al cambiar
            const estadoSel = document.getElementById('portal-notif-estado');
            if (estadoSel) {
                estadoSel.addEventListener('change', function () {
                    if (window.PortalNotificacionController) {
                        window.PortalNotificacionController.applyFilters();
                    }
                });
            }

            // Botones del toolbar de la página dedicada
            document.querySelectorAll('[data-toolbar="portal-notificaciones"] [data-action="clear-filters"]').forEach(function (b) {
                b.addEventListener('click', function () {
                    if (window.PortalNotificacionController) {
                        window.PortalNotificacionController.clearFilters();
                    }
                });
            });
            document.querySelectorAll('[data-toolbar="portal-notificaciones"] [data-action="mark-all"]').forEach(function (b) {
                b.addEventListener('click', function () {
                    if (window.PortalNotificacionController) {
                        window.PortalNotificacionController.markAllRead();
                    }
                });
            });

            // Click en fila de la tabla (delegation sobre tbody).
            // Sobrevive a re-renders: renderTable solo cambia innerHTML,
            // el listener del tbody se mantiene.
            const tbody = document.getElementById('portal-notificaciones-list');
            if (tbody) {
                tbody.addEventListener('click', function (e) {
                    const tr = e.target.closest('tr[data-notif-row-id]');
                    if (!tr) return;
                    const id = tr.dataset.notifRowId;
                    const url = tr.dataset.notifUrl || '';
                    if (!id) return;
                    if (window.PortalNotificacionController) {
                        window.PortalNotificacionController.markRead(id, url);
                    }
                });
            }

            // ── Modal de detalle ──
            document.querySelectorAll('[data-action="close-notif-detail"]').forEach(function (b) {
                b.addEventListener('click', function () {
                    if (window.PortalNotificacionView) {
                        window.PortalNotificacionView.closeDetailModal();
                    }
                });
            });

            const detailModal = document.getElementById('portal-notif-detail-modal');
            if (detailModal) {
                detailModal.addEventListener('mousedown', function (e) {
                    if (e.target === detailModal) {
                        if (window.PortalNotificacionView) {
                            window.PortalNotificacionView.closeDetailModal();
                        }
                    }
                });
                detailModal.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') {
                        e.preventDefault();
                        if (window.PortalNotificacionView) {
                            window.PortalNotificacionView.closeDetailModal();
                        }
                    }
                });
            }
        }
    };
})();