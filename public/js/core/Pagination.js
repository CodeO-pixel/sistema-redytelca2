// js/core/Pagination.js
(function () {
    'use strict';

    window.Pagination = {
        render: function (containerId, page, totalItems, onPageClick) {
            const container = document.getElementById(containerId);
            if (!container) return;
            const size = window.pageSize || 8;
            const totalPages = Math.max(1, Math.ceil(totalItems / size));
            const safePage = Math.min(Math.max(1, page), totalPages);

            container.innerHTML = `
                <div class="pagination-controls">
                    <button type="button" data-page="${Math.max(1, safePage - 1)}" ${safePage <= 1 ? 'disabled' : ''}>Anterior</button>
                    <span>Página ${safePage} / ${totalPages}</span>
                    <button type="button" data-page="${Math.min(totalPages, safePage + 1)}" ${safePage >= totalPages ? 'disabled' : ''}>Siguiente</button>
                </div>
            `;

            container.querySelectorAll('button[data-page]').forEach(btn => {
                btn.addEventListener('click', () => {
                    if (btn.disabled) return;
                    const p = parseInt(btn.getAttribute('data-page'), 10);
                    if (p >= 1) onPageClick(p);
                });
            });
        }
    };
})();