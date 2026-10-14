<?php
/**
 * Вкладка «Достижения» — справочник + просмотр выданных.
 */
$achievements = $pdo->query("SELECT * FROM achievements ORDER BY sort_order, id")->fetchAll();

$stats = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM achievements) AS total,
        (SELECT COUNT(*) FROM achievements WHERE is_active = 1) AS active,
        (SELECT COUNT(*) FROM student_achievements) AS awarded
")->fetch();
?>

<div class="adm-card">
    <div class="adm-card-header">
        <h3 class="adm-card-title">
            <i class="bi bi-trophy-fill"></i>
            Достижения
        </h3>
        <div style="display:flex;gap:8px;align-items:center;">
            <span class="adm-card-badge" id="achTotalBadge">
                <?= (int)$stats['total'] ?> всего · <?= (int)$stats['awarded'] ?> выдано
            </span>
            <button class="adm-btn adm-btn--primary adm-btn--sm" onclick="AchTab.openCreate()">
                <i class="bi bi-plus-circle-fill"></i> Добавить
            </button>
        </div>
    </div>

    <div id="achTableWrap">
        <div class="adm-table-wrap">
            <table class="adm-table">
                <thead>
                <tr>
                    <th style="width:60px;" class="center">ID</th>
                    <th style="width:70px;" class="center">Иконка</th>
                    <th>Название</th>
                    <th style="width:150px;">Код</th>
                    <th style="width:120px;" class="center">Категория</th>
                    <th style="width:180px;">Условие</th>
                    <th style="width:80px;" class="center">Очки</th>
                    <th style="width:80px;" class="center">Статус</th>
                    <th style="width:120px;" class="center">Действия</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($achievements as $a):
                    $catLabels = [
                        'attendance' => ['Посещаемость', 'primary'],
                        'works'      => ['Работы', 'success'],
                        'streak'     => ['Серия', 'warning'],
                        'special'    => ['Особая', 'danger'],
                    ];
                    $cat = $catLabels[$a['category']] ?? ['Другое', 'secondary'];

                    $condLabel = '';
                    switch ($a['condition_type']) {
                        case 'attendance_pct':
                            $condLabel = $a['condition_value'] . '% посещаемости'
                                . ($a['condition_period'] ? " за {$a['condition_period']} дн." : '');
                            break;
                        case 'works_count':
                            $condLabel = "{$a['condition_value']} работ";
                            break;
                        case 'streak_lessons':
                            $condLabel = "{$a['condition_value']} пар подряд";
                            break;
                        case 'no_absence_days':
                            $condLabel = "{$a['condition_value']} дней без пропусков";
                            break;
                        case 'rank_group':
                            $condLabel = "Топ-{$a['condition_value']} в группе";
                            break;
                        default:
                            $condLabel = $a['condition_type'];
                    }
                ?>
                    <tr>
                        <td class="num" style="color:var(--adm-text-soft);font-family:'SF Mono',monospace;font-size:0.78rem;">
                            #<?= (int)$a['id'] ?>
                        </td>
                        <td class="num">
                            <div style="width:38px;height:38px;border-radius:12px;
                                        background:<?= htmlspecialchars($a['color']) ?>20;
                                        color:<?= htmlspecialchars($a['color']) ?>;
                                        display:flex;align-items:center;justify-content:center;
                                        font-size:1.15rem;margin:0 auto;">
                                <?php if (!empty($a['svg'])): ?>
                                    <?= $a['svg'] ?>
                                <?php else: ?>
                                    <i class="bi <?= htmlspecialchars($a['icon'] ?: 'bi-trophy-fill') ?>"></i>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight:600;font-size:0.88rem;color:var(--adm-text);"><?= htmlspecialchars($a['title']) ?></div>
                            <div style="font-size:0.75rem;color:var(--adm-text-soft);"><?= htmlspecialchars($a['description'] ?? '') ?></div>
                        </td>
                        <td><code style="background:rgba(0,113,227,0.08);color:var(--adm-accent);padding:2px 8px;border-radius:6px;font-size:0.72rem;"><?= htmlspecialchars($a['code']) ?></code></td>
                        <td class="num">
                            <span class="adm-status adm-status--<?= $cat[1] === 'primary' ? 'type' : ($cat[1] === 'success' ? 'active' : ($cat[1] === 'warning' ? 'seminar' : 'manual')) ?>">
                                <?= htmlspecialchars($cat[0]) ?>
                            </span>
                        </td>
                        <td style="font-size:0.82rem;color:var(--adm-text-soft);"><?= htmlspecialchars($condLabel) ?></td>
                        <td class="num">
                            <span style="color:var(--adm-orange);font-weight:700;"><?= (int)$a['points'] ?></span>
                        </td>
                        <td class="num">
                            <span class="adm-status adm-status--<?= $a['is_active'] ? 'active' : 'blocked' ?>">
                                <?= $a['is_active'] ? 'Активно' : 'Скрыто' ?>
                            </span>
                        </td>
                        <td class="num">
                            <div class="adm-row-actions">
                                <button class="adm-icon-btn adm-icon-btn--warning"
                                        onclick="AchTab.toggle(<?= (int)$a['id'] ?>, <?= $a['is_active'] ? 1 : 0 ?>)"
                                        title="Скрыть/показать">
                                    <i class="bi bi-<?= $a['is_active'] ? 'eye-slash' : 'eye' ?>"></i>
                                </button>
                                <button class="adm-icon-btn adm-icon-btn--primary"
                                        onclick='AchTab.openEdit(<?= json_encode($a, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>)'
                                        title="Редактировать">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="adm-icon-btn adm-icon-btn--danger"
                                        onclick="AchTab.delete(<?= (int)$a['id'] ?>, '<?= htmlspecialchars(addslashes($a['title'])) ?>')"
                                        title="Удалить">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Модалка -->
<div class="adm-modal-backdrop" id="achEditModal">
    <div class="adm-modal adm-modal--wide">
        <div class="adm-modal-header">
            <h5><i class="bi bi-trophy-fill"></i> <span id="achModalTitle">Новое достижение</span></h5>
            <button class="adm-modal-close" onclick="Adm.closeModal('achEditModal')">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form id="achForm" onsubmit="AchTab.submit(event)">
            <div class="adm-modal-body">
                <input type="hidden" id="achId" name="id">

                <div class="adm-form-row">
                    <div class="adm-form-group">
                        <label class="adm-form-label"><i class="bi bi-key-fill"></i> Код <span class="req">*</span></label>
                        <input type="text" name="code" id="achCode" class="adm-input" required
                               pattern="[a-z0-9_]+" placeholder="attendance_100_month">
                        <div class="adm-form-hint">Только латиница в нижнем регистре, цифры, _</div>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-form-label"><i class="bi bi-tag-fill"></i> Категория</label>
                        <select name="category" id="achCategory" class="adm-select">
                            <option value="attendance">Посещаемость</option>
                            <option value="works">Работы</option>
                            <option value="streak">Серия</option>
                            <option value="special">Особая</option>
                        </select>
                    </div>
                </div>

                <div class="adm-form-group">
                    <label class="adm-form-label"><i class="bi bi-type-h1"></i> Название <span class="req">*</span></label>
                    <input type="text" name="title" id="achTitle" class="adm-input" required maxlength="150">
                </div>

                <div class="adm-form-group">
                    <label class="adm-form-label"><i class="bi bi-text-paragraph"></i> Описание</label>
                    <textarea name="description" id="achDesc" class="adm-input" rows="2"
                              placeholder="Что нужно сделать, чтобы получить"></textarea>
                </div>

                <div class="adm-form-row">
                    <div class="adm-form-group">
                        <label class="adm-form-label"><i class="bi bi-palette-fill"></i> Цвет</label>
                        <input type="color" name="color" id="achColor" class="adm-input" value="#0071e3"
                               style="height:44px;padding:4px;">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-form-label"><i class="bi bi-star-fill"></i> Очки</label>
                        <input type="number" name="points" id="achPoints" class="adm-input" value="10" min="0" max="1000">
                    </div>
                </div>

                <div class="adm-form-group">
                    <label class="adm-form-label"><i class="bi bi-image-fill"></i> Иконка (bootstrap-icons)</label>
                    <input type="text" name="icon" id="achIcon" class="adm-input"
                           placeholder="bi-trophy-fill" value="bi-trophy-fill">
                    <div class="adm-form-hint">
                        Список: <a href="https://icons.getbootstrap.com/" target="_blank">icons.getbootstrap.com</a>
                    </div>
                </div>

                <div class="adm-form-group">
                    <label class="adm-form-label"><i class="bi bi-code-square"></i> Свой SVG (опционально)</label>
                    <textarea name="svg" id="achSvg" class="adm-input" rows="3"
                              placeholder="<svg>...</svg> — если хочешь свою иконку"></textarea>
                </div>

                <div class="adm-form-row">
                    <div class="adm-form-group">
                        <label class="adm-form-label"><i class="bi bi-sliders"></i> Тип условия</label>
                        <select name="condition_type" id="achCondType" class="adm-select">
                            <option value="attendance_pct">Посещаемость %</option>
                            <option value="works_count">Сдано работ</option>
                            <option value="streak_lessons">Серия пар без пропусков</option>
                            <option value="no_absence_days">Дней без пропусков</option>
                            <option value="rank_group">Топ-N в группе</option>
                        </select>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-form-label"><i class="bi bi-hash"></i> Значение условия</label>
                        <input type="number" name="condition_value" id="achCondValue" class="adm-input"
                               value="100" step="0.01" required>
                    </div>
                </div>

                <div class="adm-form-row">
                    <div class="adm-form-group">
                        <label class="adm-form-label"><i class="bi bi-calendar-range"></i> Период (дней)</label>
                        <input type="number" name="condition_period" id="achCondPeriod" class="adm-input"
                               placeholder="30 = за месяц, пусто = за всё время">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-form-label"><i class="bi bi-sort-numeric-up"></i> Порядок</label>
                        <input type="number" name="sort_order" id="achSort" class="adm-input" value="0">
                    </div>
                </div>

                <div class="adm-form-group">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:0.88rem;">
                        <input type="checkbox" name="is_active" id="achActive" class="adm-checkbox" checked>
                        Активно
                    </label>
                </div>

                <div class="adm-form-group">
                    <label class="adm-form-label"><i class="bi bi-eye-fill"></i> Предпросмотр</label>
                    <div id="achPreview" style="padding:16px;border-radius:14px;background:#fafafc;border:1px solid #eaeaea;display:flex;align-items:center;gap:12px;">
                        <!-- Заполняется JS -->
                    </div>
                </div>
            </div>
            <div class="adm-modal-footer">
                <button type="button" class="adm-btn adm-btn--secondary" onclick="Adm.closeModal('achEditModal')">Отмена</button>
                <button type="submit" class="adm-btn adm-btn--primary">
                    <i class="bi bi-check-circle-fill"></i> Сохранить
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    'use strict';

    function updatePreview() {
        const icon  = document.getElementById('achIcon').value.trim() || 'bi-trophy-fill';
        const svg   = document.getElementById('achSvg').value.trim();
        const color = document.getElementById('achColor').value || '#0071e3';
        const title = document.getElementById('achTitle').value.trim() || 'Название';
        const desc  = document.getElementById('achDesc').value.trim() || 'Описание достижения';
        const pts   = document.getElementById('achPoints').value || '0';

        document.getElementById('achPreview').innerHTML = `
            <div style="width:52px;height:52px;border-radius:14px;
                        background:${color}20;color:${color};
                        display:flex;align-items:center;justify-content:center;
                        font-size:1.6rem;flex-shrink:0;">
                ${svg || `<i class="bi ${icon}"></i>`}
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-weight:700;font-size:1rem;color:#1d1d1f;margin-bottom:2px;">${title}</div>
                <div style="font-size:0.82rem;color:#86868b;">${desc}</div>
            </div>
            <div style="font-weight:700;color:${color};font-size:1.1rem;flex-shrink:0;">+${pts}</div>
        `;
    }

    ['achIcon','achSvg','achColor','achTitle','achDesc','achPoints'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', updatePreview);
            el.addEventListener('change', updatePreview);
        }
    });

    const AchTab = {
        openCreate() {
            document.getElementById('achModalTitle').textContent = 'Новое достижение';
            document.getElementById('achForm').reset();
            document.getElementById('achId').value = '';
            document.getElementById('achActive').checked = true;
            document.getElementById('achColor').value = '#0071e3';
            document.getElementById('achIcon').value = 'bi-trophy-fill';
            document.getElementById('achPoints').value = 10;
            document.getElementById('achSort').value = 0;
            updatePreview();
            Adm.openModal('achEditModal');
        },

        openEdit(a) {
            document.getElementById('achModalTitle').textContent = 'Редактировать достижение';
            document.getElementById('achId').value = a.id;
            document.getElementById('achCode').value = a.code || '';
            document.getElementById('achCategory').value = a.category || 'attendance';
            document.getElementById('achTitle').value = a.title || '';
            document.getElementById('achDesc').value = a.description || '';
            document.getElementById('achColor').value = a.color || '#0071e3';
            document.getElementById('achPoints').value = a.points || 10;
            document.getElementById('achIcon').value = a.icon || 'bi-trophy-fill';
            document.getElementById('achSvg').value = a.svg || '';
            document.getElementById('achCondType').value = a.condition_type || 'attendance_pct';
            document.getElementById('achCondValue').value = a.condition_value || 0;
            document.getElementById('achCondPeriod').value = a.condition_period || '';
            document.getElementById('achSort').value = a.sort_order || 0;
            document.getElementById('achActive').checked = parseInt(a.is_active, 10) === 1;
            updatePreview();
            Adm.openModal('achEditModal');
        },

        async submit(e) {
            e.preventDefault();
            const btn = e.target.querySelector('button[type="submit"]');
            btn.disabled = true;

            const fd = new FormData(e.target);
            const data = Object.fromEntries(fd.entries());
            data.is_active = document.getElementById('achActive').checked ? 1 : 0;

            const action = data.id ? 'achievement_update' : 'achievement_create';

            try {
                await Adm.apiCall(action, data);
                Adm.showToast(data.id ? 'Обновлено' : 'Создано');
                Adm.closeModal('achEditModal');
                setTimeout(() => location.reload(), 400);
            } catch (err) { Adm.showToast(err.message, true); }
            finally { btn.disabled = false; }
        },

        async toggle(id, active) {
            try {
                await Adm.apiCall('achievement_toggle', { id, active: active ? 0 : 1 });
                Adm.showToast(active ? 'Скрыто' : 'Активировано');
                setTimeout(() => location.reload(), 400);
            } catch (e) { Adm.showToast(e.message, true); }
        },

        delete(id, title) {
            Adm.confirmDialog(
                `Удалить достижение «${title}»?\n\nВсе присвоения студентам также удалятся.`,
                async () => {
                    try {
                        await Adm.apiCall('achievement_delete', { id });
                        Adm.showToast('Удалено');
                        setTimeout(() => location.reload(), 400);
                    } catch (e) { Adm.showToast(e.message, true); }
                }
            );
        },
    };

    window.AchTab = AchTab;
})();
</script>