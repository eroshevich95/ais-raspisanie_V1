<?php
/**
 * Вкладка «Сброс пароля».
 */
?>

<div class="adm-card">
    <div class="adm-card-header">
        <h3 class="adm-card-title">
            <i class="bi bi-key-fill"></i>
            Запросы на сброс пароля
        </h3>
        <div style="display:flex; gap:8px; align-items:center;">
            <span class="adm-card-badge" id="resetsTotalBadge">— активно</span>
            <button class="adm-btn adm-btn--secondary adm-btn--sm" onclick="ResetsTab.reload()">
                <i class="bi bi-arrow-clockwise"></i> Обновить
            </button>
        </div>
    </div>

    <!-- Активные запросы -->
    <div id="resetsActiveWrap">
        <div class="adm-skeleton" style="padding: 20px 24px;">
            <div class="adm-skeleton-row"></div>
            <div class="adm-skeleton-row"></div>
        </div>
    </div>
</div>

<!-- История -->
<div class="adm-card">
    <div class="adm-card-header">
        <h3 class="adm-card-title">
            <i class="bi bi-clock-history"></i>
            История обработки
        </h3>
        <span class="adm-card-badge">Последние 50</span>
    </div>
    <div id="resetsHistoryWrap">
        <div class="adm-skeleton" style="padding: 20px 24px;">
            <div class="adm-skeleton-row"></div>
            <div class="adm-skeleton-row"></div>
            <div class="adm-skeleton-row"></div>
        </div>
    </div>
</div>

<!-- Модалка: отклонение с причиной -->
<div class="adm-modal-backdrop" id="rejectResetModal">
    <div class="adm-modal">
        <div class="adm-modal-header">
            <h5><i class="bi bi-x-circle-fill danger"></i> Отклонить запрос</h5>
            <button class="adm-modal-close" onclick="Adm.closeModal('rejectResetModal')">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="adm-modal-body">
            <input type="hidden" id="rejectResetId">
            <div class="adm-form-group">
                <label class="adm-form-label"><i class="bi bi-chat-left-text"></i> Причина (необязательно)</label>
                <textarea id="rejectResetComment" class="adm-input" rows="3"
                          placeholder="Например: не совпадают данные"
                          style="resize: vertical;"></textarea>
            </div>
            <div class="adm-form-hint" id="rejectResetUser"></div>
        </div>
        <div class="adm-modal-footer">
            <button type="button" class="adm-btn adm-btn--secondary" onclick="Adm.closeModal('rejectResetModal')">
                Отмена
            </button>
            <button type="button" class="adm-btn adm-btn--danger" onclick="ResetsTab.applyReject()">
                <i class="bi bi-x-lg"></i> Отклонить
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    let loaded = false;

    async function load() {
        const activeWrap = document.getElementById('resetsActiveWrap');
        const historyWrap = document.getElementById('resetsHistoryWrap');

        try {
            const [active, history] = await Promise.all([
                Adm.apiCall('resets_list'),
                Adm.apiCall('resets_history'),
            ]);

            renderActive(active.rows);
            renderHistory(history.rows);

            document.getElementById('resetsTotalBadge').textContent = active.rows.length + ' активно';
            document.getElementById('tabCountResets').textContent = active.rows.length || '—';

            loaded = true;
        } catch (e) {
            activeWrap.innerHTML = Adm.emptyState('bi-exclamation-triangle', 'Ошибка', e.message);
            historyWrap.innerHTML = '';
        }
    }

    function renderActive(rows) {
        const wrap = document.getElementById('resetsActiveWrap');

        if (!rows.length) {
            wrap.innerHTML = Adm.emptyState(
                'bi-check-circle-fill',
                'Все чисто!',
                'Нет активных запросов на сброс пароля'
            );
            return;
        }

        wrap.innerHTML = `
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th style="width:130px;">Дата</th>
                            <th>Логин</th>
                            <th>Email</th>
                            <th>Пользователь</th>
                            <th class="center" style="width:230px;">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rows.map(r => `
                            <tr>
                                <td style="font-size:0.78rem;color:var(--adm-text-soft);">
                                    ${Adm.fmtDate(r.created_at, 'short')}
                                </td>
                                <td>
                                    <code style="background:rgba(0,113,227,0.08);color:var(--adm-accent);padding:2px 8px;border-radius:6px;font-size:0.75rem;font-family:'SF Mono',monospace;">
                                        ${Adm.escapeHtml(r.login)}
                                    </code>
                                </td>
                                <td style="font-size:0.82rem;">${Adm.escapeHtml(r.email)}</td>
                                <td style="font-size:0.82rem;">${Adm.escapeHtml(r.full_name || '—')}</td>
                                <td class="num">
                                    <div class="adm-row-actions" style="opacity:1;">
                                        <button class="adm-btn adm-btn--success adm-btn--sm" onclick="ResetsTab.approve(${r.id})">
                                            <i class="bi bi-check-lg"></i> Одобрить
                                        </button>
                                        <button class="adm-btn adm-btn--danger adm-btn--sm" onclick="ResetsTab.openReject(${r.id}, '${Adm.escapeHtml(r.login)}')">
                                            <i class="bi bi-x-lg"></i> Отклонить
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }

    function renderHistory(rows) {
        const wrap = document.getElementById('resetsHistoryWrap');
        if (!rows.length) {
            wrap.innerHTML = Adm.emptyState('bi-clock-history', 'Пока ничего нет');
            return;
        }

        const statusLabels = {
            'approved': ['Одобрено', 'active'],
            'rejected': ['Отклонено', 'blocked'],
            'used':     ['Использовано', 'type'],
        };

        wrap.innerHTML = `
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th style="width:130px;">Дата</th>
                            <th>Логин</th>
                            <th class="center" style="width:130px;">Статус</th>
                            <th>Админ</th>
                            <th>Комментарий</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rows.map(h => {
                            const s = statusLabels[h.status] || ['—', 'type'];
                            return `
                                <tr>
                                    <td style="font-size:0.78rem;color:var(--adm-text-soft);">
                                        ${h.processed_at ? Adm.fmtDate(h.processed_at, 'short') : '—'}
                                    </td>
                                    <td>
                                        <code style="background:rgba(0,113,227,0.08);color:var(--adm-accent);padding:2px 8px;border-radius:6px;font-size:0.75rem;font-family:'SF Mono',monospace;">
                                            ${Adm.escapeHtml(h.login)}
                                        </code>
                                    </td>
                                    <td class="num">
                                        <span class="adm-status adm-status--${s[1]}">${s[0]}</span>
                                    </td>
                                    <td style="font-size:0.82rem;">${Adm.escapeHtml(h.admin_name || '—')}</td>
                                    <td style="font-size:0.82rem;color:var(--adm-text-soft);">
                                        ${Adm.escapeHtml(h.admin_comment || '')}
                                    </td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }

    const ResetsTab = {
        reload() { load(); },
        async approve(id) {
            Adm.confirmDialog(
                'Одобрить запрос на сброс пароля?\n\nБудет сгенерирована ссылка, которую нужно передать пользователю.',
                async () => {
                    try {
                        const res = await Adm.apiCall('reset_approve', { request_id: id });
                        Adm.showToast('Одобрено');
                        if (res.link) {
                            setTimeout(() => {
                                prompt('Ссылка для сброса пароля (скопируйте и передайте пользователю):', res.link);
                            }, 400);
                        }
                        load();
                    } catch (e) { Adm.showToast(e.message, true); }
                },
                { danger: false }
            );
        },
        openReject(id, login) {
            document.getElementById('rejectResetId').value = id;
            document.getElementById('rejectResetComment').value = '';
            document.getElementById('rejectResetUser').textContent = 'Пользователь: ' + login;
            Adm.openModal('rejectResetModal');
        },
        async applyReject() {
            const id = document.getElementById('rejectResetId').value;
            const comment = document.getElementById('rejectResetComment').value;
            Adm.closeModal('rejectResetModal');
            try {
                await Adm.apiCall('reset_reject', { request_id: id, comment });
                Adm.showToast('Отклонено');
                load();
            } catch (e) { Adm.showToast(e.message, true); }
        },
    };

    window.ResetsTab = ResetsTab;

    Adm.registerTab('resets', {
        onInit: load,
        onShow: () => { if (loaded) load(); }, // обновляем при каждом заходе
    });
})();
</script>