<?php
/**
 * Вкладка «Настройки».
 */
require_once __DIR__ . '/../../includes/settings.php';

$maintenanceEnabled = getSetting($pdo, 'maintenance_enabled', '0') === '1';
$maintenanceTitle   = getSetting($pdo, 'maintenance_title',   'Идут технические работы');
$maintenanceMessage = getSetting($pdo, 'maintenance_message', 'Мы обновляем систему. Скоро вернёмся.');
$maintenanceUntil   = getSetting($pdo, 'maintenance_until',   '');
?>

<!-- ============================================================
     РЕЖИМ ТЕХ. ОБСЛУЖИВАНИЯ
     ============================================================ -->
<div class="adm-card" style="border:2px solid <?= $maintenanceEnabled ? 'rgba(255,159,10,0.4)' : 'transparent' ?>;">
    <div class="adm-card-header" style="background: <?= $maintenanceEnabled
        ? 'linear-gradient(135deg, rgba(255,159,10,0.12) 0%, #fff 100%)'
        : 'linear-gradient(135deg, #fafafc 0%, #fff 100%)' ?>;">
        <h3 class="adm-card-title">
            <i class="bi bi-cone-striped" style="color: var(--adm-orange);"></i>
            Режим технического обслуживания
        </h3>
        <span class="adm-card-badge" id="maintenanceStatusBadge"
              style="<?= $maintenanceEnabled ? 'background: rgba(255,159,10,0.15); color:#b25c00;' : '' ?>">
            <?= $maintenanceEnabled ? 'Включён' : 'Выключен' ?>
        </span>
    </div>
    <div class="adm-card-body">
        <p style="font-size:0.88rem;color:var(--adm-text-soft);margin:0 0 20px;line-height:1.5;">
            При включении все пользователи, кроме администраторов,
            будут перенаправлены на специальную страницу.
            Администраторы сохранят полный доступ, чтобы можно было выключить режим.
        </p>

        <form id="maintenanceForm" onsubmit="SettingsTab.saveMaintenance(event)">

            <!-- Тумблер -->
            <div class="adm-setting-item" style="margin-bottom:16px;">
                <div style="flex:1;min-width:0;">
                    <div class="lbl" style="color:var(--adm-text);font-weight:700;margin-bottom:4px;">
                        <i class="bi bi-power" style="color: <?= $maintenanceEnabled ? 'var(--adm-orange)' : 'var(--adm-accent)' ?>;"></i>
                        Режим тех. обслуживания
                    </div>
                    <div style="font-size:0.75rem;color:var(--adm-text-soft);">
                        <?= $maintenanceEnabled
                            ? 'Сайт закрыт для обычных пользователей.'
                            : 'Сайт работает в обычном режиме.' ?>
                    </div>
                </div>
                <label style="position:relative;display:inline-block;width:52px;height:28px;flex-shrink:0;">
                    <input type="checkbox" name="maintenance_enabled"
                           id="maintenanceToggle"
                           <?= $maintenanceEnabled ? 'checked' : '' ?>
                           style="opacity:0;width:0;height:0;"
                           onchange="SettingsTab.toggleLabel(this)">
                    <span id="maintenanceSlider"
                          style="position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;
                                 background:<?= $maintenanceEnabled ? 'linear-gradient(135deg,#ff9f0a,#b25c00)' : '#d2d2d7' ?>;
                                 border-radius:28px;transition:0.3s;"></span>
                </label>
            </div>

            <!-- Заголовок -->
            <div class="adm-form-group">
                <label class="adm-form-label">
                    <i class="bi bi-type-h1"></i> Заголовок на странице
                </label>
                <input type="text" name="maintenance_title" class="adm-input"
                       value="<?= htmlspecialchars($maintenanceTitle) ?>"
                       maxlength="255"
                       placeholder="Идут технические работы">
            </div>

            <!-- Текст -->
            <div class="adm-form-group">
                <label class="adm-form-label">
                    <i class="bi bi-text-paragraph"></i> Текст сообщения
                </label>
                <textarea name="maintenance_message" class="adm-input" rows="4"
                          style="resize: vertical; min-height: 90px;"
                          placeholder="Мы обновляем систему. Скоро вернёмся."><?= htmlspecialchars($maintenanceMessage) ?></textarea>
                <div class="adm-form-hint">
                    Показывается пользователям на странице тех. обслуживания.
                </div>
            </div>

            <!-- Дата окончания -->
            <div class="adm-form-group">
                <label class="adm-form-label">
                    <i class="bi bi-calendar-event"></i> Ожидаемое время окончания (опционально)
                </label>
                <input type="datetime-local" name="maintenance_until" class="adm-input"
                       value="<?= htmlspecialchars(
                           $maintenanceUntil ? str_replace(' ', 'T', substr($maintenanceUntil, 0, 16)) : ''
                       ) ?>">
                <div class="adm-form-hint">
                    Если указать — на странице появится плашка «Ожидаем вернуться: …».
                </div>
            </div>

            <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-top:20px;">
                <button type="submit" class="adm-btn adm-btn--primary">
                    <i class="bi bi-check-circle-fill"></i> Сохранить настройки
                </button>
                <a href="../maintenance.php" target="_blank" class="adm-btn adm-btn--secondary">
                    <i class="bi bi-box-arrow-up-right"></i> Открыть страницу
                </a>
                <?php if ($maintenanceEnabled): ?>
                    <span style="font-size:0.78rem;color:#b25c00;display:inline-flex;align-items:center;gap:6px;">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        Сейчас сайт <strong>закрыт</strong> для всех, кроме админов.
                    </span>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     ИНФОРМАЦИЯ О СИСТЕМЕ
     ============================================================ -->
<div class="adm-card">
    <div class="adm-card-header">
        <h3 class="adm-card-title">
            <i class="bi bi-info-circle-fill"></i>
            Информация о системе
        </h3>
        <button class="adm-btn adm-btn--secondary adm-btn--sm" onclick="SettingsTab.reloadInfo()">
            <i class="bi bi-arrow-clockwise"></i> Обновить
        </button>
    </div>
    <div class="adm-card-body">
        <div class="adm-settings-grid" id="systemInfoGrid">
            <div class="adm-setting-item">
                <span class="lbl"><i class="bi bi-hourglass-split"></i> Загрузка...</span>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     РЕЗЕРВНАЯ КОПИЯ БД
     ============================================================ -->
<div class="adm-card">
    <div class="adm-card-header">
        <h3 class="adm-card-title">
            <i class="bi bi-database-fill-down"></i>
            Резервная копия БД
        </h3>
    </div>
    <div class="adm-card-body">
        <p style="font-size:0.88rem;color:var(--adm-text-soft);margin:0 0 16px;line-height:1.5;">
            Скачать полный дамп базы данных в формате <code>.sql</code>.
            Дамп содержит структуру всех таблиц и все данные.
        </p>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <button class="adm-btn adm-btn--primary" onclick="SettingsTab.downloadBackup()">
                <i class="bi bi-download"></i> Скачать дамп БД
            </button>
        </div>
        <div class="adm-form-hint" style="margin-top:12px;">
            <i class="bi bi-info-circle"></i>
            На больших БД скачивание может занять 10-30 секунд. Не закрывайте вкладку.
        </div>
    </div>
</div>

<!-- ============================================================
     ОБСЛУЖИВАНИЕ
     ============================================================ -->
<div class="adm-card">
    <div class="adm-card-header">
        <h3 class="adm-card-title">
            <i class="bi bi-tools"></i>
            Обслуживание
        </h3>
    </div>
    <div class="adm-card-body">
        <div class="adm-settings-grid">
            <div class="adm-setting-item">
                <div style="flex:1;min-width:0;">
                    <div class="lbl" style="color:var(--adm-text);font-weight:700;margin-bottom:4px;">
                        <i class="bi bi-arrow-repeat"></i> Кэш справочника
                    </div>
                    <div style="font-size:0.75rem;color:var(--adm-text-soft);">
                        Институты и кафедры с портала КузГТУ. Кэшируется на 24 часа.
                    </div>
                </div>
                <button class="adm-btn adm-btn--secondary adm-btn--sm" onclick="SettingsTab.rebuildDepartmentsCache()">
                    <i class="bi bi-trash"></i> Сбросить
                </button>
            </div>

            <div class="adm-setting-item">
                <div style="flex:1;min-width:0;">
                    <div class="lbl" style="color:var(--adm-text);font-weight:700;margin-bottom:4px;">
                        <i class="bi bi-bug-fill"></i> Очистить старые занятия
                    </div>
                    <div style="font-size:0.75rem;color:var(--adm-text-soft);">
                        Удалить занятия, посещаемость и работы до указанной даты.
                    </div>
                </div>
                <button class="adm-btn adm-btn--secondary adm-btn--sm" onclick="SettingsTab.openPurge()">
                    <i class="bi bi-eraser"></i> Открыть
                </button>
            </div>

            <div class="adm-setting-item">
                <div style="flex:1;min-width:0;">
                    <div class="lbl" style="color:var(--adm-text);font-weight:700;margin-bottom:4px;">
                        <i class="bi bi-journal-text"></i> Журнал действий
                    </div>
                    <div style="font-size:0.75rem;color:var(--adm-text-soft);">
                        Полный журнал с фильтрами и пагинацией.
                    </div>
                </div>
                <button class="adm-btn adm-btn--secondary adm-btn--sm" onclick="Adm.showTab('audit')">
                    <i class="bi bi-arrow-right"></i> Открыть
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     ОПАСНАЯ ЗОНА
     ============================================================ -->
<div class="adm-card">
    <div class="adm-card-header">
        <h3 class="adm-card-title">
            <i class="bi bi-shield-exclamation" style="color:var(--adm-orange);"></i>
            Опасная зона
        </h3>
    </div>
    <div class="adm-card-body">
        <div class="adm-setting-item" style="background:rgba(255,69,58,0.05);border-color:rgba(255,69,58,0.15);">
            <div style="flex:1;min-width:0;">
                <div class="lbl" style="color:#842029;font-weight:700;margin-bottom:4px;">
                    <i class="bi bi-exclamation-triangle-fill"></i> Массовое удаление занятий
                </div>
                <div style="font-size:0.75rem;color:var(--adm-text-soft);">
                    Удаление занятий до определённой даты вместе со всей посещаемостью и работами. Необратимо.
                </div>
            </div>
            <button class="adm-btn adm-btn--danger adm-btn--sm" onclick="SettingsTab.openPurge()">
                <i class="bi bi-eraser"></i> Открыть
            </button>
        </div>
    </div>
</div>

<!-- Модалка: очистка старых -->
<div class="adm-modal-backdrop" id="settingsPurgeModal">
    <div class="adm-modal">
        <div class="adm-modal-header">
            <h5><i class="bi bi-eraser-fill danger"></i> Очистить старые занятия</h5>
            <button class="adm-modal-close" onclick="Adm.closeModal('settingsPurgeModal')">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="adm-modal-body">
            <div class="adm-form-group">
                <label class="adm-form-label"><i class="bi bi-calendar-x"></i> Удалить занятия до</label>
                <input type="date" class="adm-input" id="settingsPurgeDate"
                       value="<?= date('Y-m-d', strtotime('-1 year')) ?>">
                <div class="adm-form-hint">
                    <i class="bi bi-exclamation-triangle" style="color: var(--adm-orange);"></i>
                    Будут удалены все занятия, посещаемость и работы до указанной даты.
                </div>
            </div>
        </div>
        <div class="adm-modal-footer">
            <button type="button" class="adm-btn adm-btn--secondary" onclick="Adm.closeModal('settingsPurgeModal')">
                Отмена
            </button>
            <button type="button" class="adm-btn adm-btn--danger" onclick="SettingsTab.applyPurge()">
                <i class="bi bi-trash"></i> Удалить
            </button>
        </div>
    </div>
</div>

<!-- Внутренние стили для слайдера -->
<style>
#maintenanceToggle:checked + #maintenanceSlider {
    background: linear-gradient(135deg, #ff9f0a, #b25c00);
    box-shadow: 0 4px 12px rgba(255,159,10,0.4);
}
#maintenanceToggle:checked + #maintenanceSlider::after {
    left: 26px;
}
#maintenanceSlider::after {
    content: '';
    position: absolute;
    width: 22px; height: 22px;
    background: #fff;
    border-radius: 50%;
    top: 3px; left: 3px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    transition: left 0.3s cubic-bezier(0.16,1,0.3,1);
}
</style>

<script>
(function() {
    'use strict';

    let loaded = false;

    // ============================================================
    // ИНФОРМАЦИЯ О СИСТЕМЕ
    // ============================================================
    async function loadInfo() {
        const grid = document.getElementById('systemInfoGrid');
        try {
            const res = await Adm.apiCall('system_info');
            const i = res.info;

            const items = [
                { lbl: 'PHP',                val: i.php_version,        icon: 'bi-filetype-php',      color: '#777BB4' },
                { lbl: 'MySQL',              val: i.mysql_version,      icon: 'bi-database-fill',     color: '#4479A1' },
                { lbl: 'Сервер',             val: i.server,             icon: 'bi-hdd-network-fill',  color: 'var(--adm-accent)' },
                { lbl: 'База данных',        val: i.db_name,            icon: 'bi-server',            color: 'var(--adm-purple)' },
                { lbl: 'Размер БД',          val: i.db_size_mb !== null ? i.db_size_mb + ' МБ' : '—',
                  icon: 'bi-hdd-fill',      color: 'var(--adm-green)' },
                { lbl: 'Таблиц',             val: i.db_tables !== null ? i.db_tables : '—',
                  icon: 'bi-table',         color: 'var(--adm-orange)' },
                { lbl: 'Часовой пояс',       val: i.timezone,           icon: 'bi-clock-fill',        color: 'var(--adm-accent-2)' },
                { lbl: 'Кэш справочника',    val: i.departments_cache || 'не создан',
                  icon: 'bi-cache-fill',    color: i.departments_cache ? 'var(--adm-green)' : 'var(--adm-text-soft)' },
            ];

            grid.innerHTML = items.map(it => `
                <div class="adm-setting-item">
                    <span class="lbl">
                        <i class="bi ${it.icon}" style="color: ${it.color};"></i>
                        ${Adm.escapeHtml(it.lbl)}
                    </span>
                    <span class="val">${Adm.escapeHtml(String(it.val))}</span>
                </div>
            `).join('');
        } catch (e) {
            grid.innerHTML = `<div class="adm-setting-item"><span class="lbl">Ошибка: ${Adm.escapeHtml(e.message)}</span></div>`;
        }
    }

    const SettingsTab = {
        reloadInfo() { loadInfo(); },

        toggleLabel(cb) {
            const slider = document.getElementById('maintenanceSlider');
            // стилизация делается через CSS #maintenanceToggle:checked + #maintenanceSlider
            // но добавим лёгкую подсветку карточки
            const card = cb.closest('.adm-card');
            if (card) {
                card.style.borderColor = cb.checked
                    ? 'rgba(255,159,10,0.4)'
                    : 'transparent';
            }
        },

        async saveMaintenance(e) {
            e.preventDefault();
            const form = e.target;
            const btn = form.querySelector('button[type="submit"]');
            const orig = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" style="width:14px;height:14px;border-width:2px;"></span> Сохранение...';

            const fd = new FormData(form);
            fd.append('action', 'settings_save');
            if (!document.getElementById('maintenanceToggle').checked) {
                fd.delete('maintenance_enabled');
            }

            try {
                const res = await Adm.apiCall('settings_save', Object.fromEntries(fd.entries()));
                Adm.showToast('Настройки сохранены');
                setTimeout(() => location.reload(), 600);
            } catch (err) {
                Adm.showToast(err.message, true);
                btn.disabled = false;
                btn.innerHTML = orig;
            }
        },

        async downloadBackup() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'api.php';
            form.target = '_blank';
            form.style.display = 'none';

            const fields = {
                action: 'backup_download',
                csrf: Adm.CSRF,
            };
            for (const [k, v] of Object.entries(fields)) {
                const inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = k;
                inp.value = v;
                form.appendChild(inp);
            }
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);

            Adm.showToast('Скачивание дампа запущено...');
        },

        async rebuildDepartmentsCache() {
            Adm.confirmDialog(
                'Сбросить кэш справочника институтов и кафедр?\n\nПри следующем запросе данные будут загружены с портала КузГТУ заново.',
                async () => {
                    try {
                        await Adm.apiCall('rebuild_departments_cache');
                        Adm.showToast('Кэш сброшен');
                        loadInfo();
                    } catch (e) { Adm.showToast(e.message, true); }
                },
                { danger: false }
            );
        },

        openPurge() {
            Adm.openModal('settingsPurgeModal');
        },

        applyPurge() {
            const date = document.getElementById('settingsPurgeDate').value;
            if (!date) return;
            Adm.confirmDialog(
                `Удалить ВСЕ занятия до ${date}?\n\nЭто действие необратимо.`,
                async () => {
                    Adm.closeModal('settingsPurgeModal');
                    try {
                        const res = await Adm.apiCall('lessons_delete_old', { date });
                        Adm.showToast(`Удалено занятий: ${res.deleted}`);
                        loadInfo();
                    } catch (e) { Adm.showToast(e.message, true); }
                }
            );
        },
    };

    window.SettingsTab = SettingsTab;

    Adm.registerTab('settings', {
        onInit: () => { loadInfo(); loaded = true; },
        onShow: () => { if (loaded) loadInfo(); },
    });
})();
</script>