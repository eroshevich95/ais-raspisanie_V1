<?php
/**
 * Вкладка «Журнал».
 */
?>

<div class="adm-card">
    <div class="adm-card-header">
        <h3 class="adm-card-title">
            <i class="bi bi-clock-history"></i>
            Журнал действий
        </h3>
        <div style="display:flex; gap:8px; align-items:center;">
            <span class="adm-card-badge" id="auditTotalBadge">— записей</span>
            <a href="../audit.php" target="_blank" class="adm-btn adm-btn--secondary adm-btn--sm">
                <i class="bi bi-box-arrow-up-right"></i> Расширенный вид
            </a>
        </div>
    </div>

    <div class="adm-filters adm-filters--audit">
        <div>
            <div class="adm-filter-label"><i class="bi bi-search"></i> Поиск</div>
            <div class="adm-search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" class="adm-input" id="auditSearch"
                       placeholder="ФИО или описание..." autocomplete="off">
            </div>
        </div>
        <div>
            <div class="adm-filter-label"><i class="bi bi-funnel"></i> Действие</div>
            <select class="adm-select" id="auditActionFilter">
                <option value="">Все действия</option>
            </select>
        </div>
        <div>
            <div class="adm-filter-label"><i class="bi bi-calendar-event"></i> С</div>
            <input type="date" class="adm-input" id="auditDateFrom"
                   value="<?= date('Y-m-d', strtotime('-30 days')) ?>">
        </div>
        <div>
            <div class="adm-filter-label"><i class="bi bi-calendar-event"></i> По</div>
            <input type="date" class="adm-input" id="auditDateTo"
                   value="<?= date('Y-m-d') ?>">
        </div>
        <div>
            <button class="adm-btn adm-btn--secondary adm-btn--sm" onclick="AuditTab.resetFilters()" style="height:42px;">
                <i class="bi bi-x-circle"></i> Сбросить
            </button>
        </div>
    </div>

    <div id="auditTableWrap">
        <div class="adm-skeleton">
            <div class="adm-skeleton-row"></div>
            <div class="adm-skeleton-row"></div>
            <div class="adm-skeleton-row"></div>
        </div>
    </div>

    <div id="auditPagination"></div>
</div>

<script>
(function() {
    'use strict';

    const state = {
        page: 1,
        perPage: 50,
        search: '',
        actionFilter: '',
        from: '<?= date('Y-m-d', strtotime('-30 days')) ?>',
        to: '<?= date('Y-m-d') ?>',
        loaded: false,
        actionsLoaded: false,
    };

    async function load(force = false) {
        if (state.loaded && !force) return;
        state.loaded = true;

        const wrap = document.getElementById('auditTableWrap');
        wrap.innerHTML = Adm.skeleton(10);

        try {
            const res = await Adm.apiCall('audit_list', {
                page: state.page,
                per_page: state.perPage,
                search: state.search,
                action_filter: state.actionFilter,
                from: state.from,
                to: state.to,
            });

            if (res.actions && !state.actionsLoaded) {
                const sel = document.getElementById('auditActionFilter');
                sel.innerHTML = '<option value="">Все действия</option>'
                    + res.actions.map(a =>
                        `<option value="${Adm.escapeHtml(a.action)}">${Adm.escapeHtml(a.label || a.action)}</option>`
                    ).join('');
                state.actionsLoaded = true;
            }

            document.getElementById('auditTotalBadge').textContent = res.total + ' записей';
            renderTable(res.rows);
            Adm.renderPagination(
                document.getElementById('auditPagination'),
                res.page, res.pages,
                p => { state.page = p; load(true); window.scrollTo({top:0, behavior:'smooth'}); }
            );
        } catch (e) {
            wrap.innerHTML = Adm.emptyState('bi-exclamation-triangle', 'Ошибка', e.message);
        }
    }

    function renderTable(rows) {
        const wrap = document.getElementById('auditTableWrap');
        if (!rows.length) {
            wrap.innerHTML = Adm.emptyState('bi-clock-history', 'За выбранный период ничего нет');
            return;
        }

        wrap.innerHTML = `
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th style="width:130px;">Когда</th>
                            <th style="width:220px;">Кто</th>
                            <th style="width:240px;">Действие</th>
                            <th>Что именно</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rows.map(r => {
                            const initials = (r.user_name || '?').split(' ').slice(0,2)
                                .map(w => w.charAt(0)).join('').toUpperCase();

                            return `
                                <tr>
                                    <td>
                                        <div style="font-weight:600;font-size:0.82rem;">
                                            ${Adm.fmtDate(r.created_at, 'date')}
                                        </div>
                                        <div style="font-size:0.72rem;color:var(--adm-text-soft);">
                                            ${Adm.fmtDate(r.created_at, 'time')}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="adm-user-cell">
                                            <div class="adm-user-avatar" style="width:32px;height:32px;font-size:0.7rem;">
                                                ${Adm.escapeHtml(initials || '?')}
                                            </div>
                                            <div class="adm-user-info">
                                                <div class="adm-user-name" style="font-size:0.85rem;">
                                                    ${Adm.escapeHtml(r.user_name || '—')}
                                                </div>
                                                ${r.ip ? `<div class="adm-user-login">${Adm.escapeHtml(r.ip)}</div>` : ''}
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="adm-status adm-status--type" style="font-size:0.68rem;">
                                            ${Adm.escapeHtml(r.action)}
                                        </span>
                                    </td>
                                    <td>
                                        ${r.description ? `
                                            <div style="font-size:0.85rem;color:var(--adm-text);line-height:1.4;">
                                                ${Adm.escapeHtml(r.description)}
                                            </div>
                                        ` : ''}
                                        ${r.entity_type && r.entity_id ? `
                                            <div style="font-size:0.72rem;color:var(--adm-text-soft);margin-top:4px;">
                                                <code style="background:rgba(0,113,227,0.08);color:var(--adm-accent);padding:1px 6px;border-radius:4px;">
                                                    ${Adm.escapeHtml(r.entity_type)}#${r.entity_id}
                                                </code>
                                            </div>
                                        ` : ''}
                                    </td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }

    const AuditTab = {
        resetFilters() {
            state.search = '';
            state.actionFilter = '';
            state.from = '<?= date('Y-m-d', strtotime('-30 days')) ?>';
            state.to = '<?= date('Y-m-d') ?>';
            state.page = 1;

            document.getElementById('auditSearch').value = '';
            document.getElementById('auditActionFilter').value = '';
            document.getElementById('auditDateFrom').value = state.from;
            document.getElementById('auditDateTo').value = state.to;
            load(true);
        },
        reload: () => load(true),
    };

    window.AuditTab = AuditTab;

    document.getElementById('auditSearch').addEventListener('input', Adm.debounce(e => {
        state.search = e.target.value.trim();
        state.page = 1;
        load(true);
    }, 300));

    document.getElementById('auditActionFilter').addEventListener('change', e => {
        state.actionFilter = e.target.value;
        state.page = 1;
        load(true);
    });
    document.getElementById('auditDateFrom').addEventListener('change', e => {
        state.from = e.target.value;
        state.page = 1;
        load(true);
    });
    document.getElementById('auditDateTo').addEventListener('change', e => {
        state.to = e.target.value;
        state.page = 1;
        load(true);
    });

    Adm.registerTab('audit', {
        onInit: () => load(true),
    });
})();
</script>