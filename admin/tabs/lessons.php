<?php
/**
 * Вкладка «Занятия».
 */
?>

<div class="adm-card">
    <div class="adm-card-header">
        <h3 class="adm-card-title">
            <i class="bi bi-calendar-week"></i>
            Занятия
        </h3>
        <div style="display:flex; gap:8px; align-items:center;">
            <span class="adm-card-badge" id="lessonsTotalBadge">— записей</span>
            <button class="adm-btn adm-btn--secondary adm-btn--sm" onclick="LessonsTab.openPurge()">
                <i class="bi bi-eraser"></i> Очистить старые
            </button>
        </div>
    </div>

    <!-- Фильтры -->
    <div class="adm-filters adm-filters--lessons">
        <div>
            <div class="adm-filter-label"><i class="bi bi-search"></i> Поиск</div>
            <div class="adm-search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" class="adm-input" id="lessonsSearch"
                       placeholder="Дисциплина или группа..." autocomplete="off">
            </div>
        </div>
        <div>
            <div class="adm-filter-label"><i class="bi bi-person-badge"></i> Преподаватель</div>
            <select class="adm-select" id="lessonsTeacherFilter">
                <option value="">Все</option>
            </select>
        </div>
        <div>
            <div class="adm-filter-label"><i class="bi bi-calendar-event"></i> С</div>
            <input type="date" class="adm-input" id="lessonsDateFrom">
        </div>
        <div>
            <div class="adm-filter-label"><i class="bi bi-calendar-event"></i> По</div>
            <input type="date" class="adm-input" id="lessonsDateTo">
        </div>
        <div>
            <button class="adm-btn adm-btn--secondary adm-btn--sm" onclick="LessonsTab.resetFilters()" style="height:42px;">
                <i class="bi bi-x-circle"></i> Сбросить
            </button>
        </div>
    </div>

    <!-- Массовые действия -->
    <div class="adm-bulk-bar" id="lessonsBulkBar">
        <div class="adm-bulk-count">
            <i class="bi bi-check2-circle"></i>
            Выбрано: <span id="lessonsBulkCount">0</span>
        </div>
        <div class="adm-bulk-actions">
            <button class="adm-btn adm-btn--danger adm-btn--sm" onclick="LessonsTab.bulkDelete()">
                <i class="bi bi-trash"></i> Удалить выбранные
            </button>
            <button class="adm-btn adm-btn--secondary adm-btn--sm" onclick="LessonsTab.clearSelection()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>

    <!-- Таблица -->
    <div id="lessonsTableWrap">
        <div class="adm-skeleton">
            <div class="adm-skeleton-row"></div>
            <div class="adm-skeleton-row"></div>
            <div class="adm-skeleton-row"></div>
        </div>
    </div>

    <!-- Пагинация -->
    <div id="lessonsPagination"></div>
</div>

<!-- Модалка: очистка старых -->
<div class="adm-modal-backdrop" id="lessonsPurgeModal">
    <div class="adm-modal">
        <div class="adm-modal-header">
            <h5><i class="bi bi-eraser-fill danger"></i> Очистить старые занятия</h5>
            <button class="adm-modal-close" onclick="Adm.closeModal('lessonsPurgeModal')">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="adm-modal-body">
            <div class="adm-form-group">
                <label class="adm-form-label"><i class="bi bi-calendar-x"></i> Удалить занятия до</label>
                <input type="date" class="adm-input" id="purgeDate"
                       value="<?= date('Y-m-d', strtotime('-1 year')) ?>">
                <div class="adm-form-hint">
                    <i class="bi bi-exclamation-triangle" style="color: var(--adm-orange);"></i>
                    Будут удалены все занятия, посещаемость и работы до указанной даты.
                </div>
            </div>
        </div>
        <div class="adm-modal-footer">
            <button type="button" class="adm-btn adm-btn--secondary" onclick="Adm.closeModal('lessonsPurgeModal')">
                Отмена
            </button>
            <button type="button" class="adm-btn adm-btn--danger" onclick="LessonsTab.applyPurge()">
                <i class="bi bi-trash"></i> Удалить
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    const state = {
        page: 1,
        perPage: 30,
        search: '',
        teacherId: 0,
        dateFrom: '',
        dateTo: '',
        selected: new Set(),
        loaded: false,
        teachersLoaded: false,
    };

    async function load(force = false) {
        if (state.loaded && !force) return;
        state.loaded = true;

        const wrap = document.getElementById('lessonsTableWrap');
        wrap.innerHTML = Adm.skeleton(8);

        try {
            const res = await Adm.apiCall('lessons_list', {
                page: state.page,
                per_page: state.perPage,
                search: state.search,
                teacher_id: state.teacherId,
                date_from: state.dateFrom,
                date_to: state.dateTo,
            });

            if (res.teachers && !state.teachersLoaded) {
                const sel = document.getElementById('lessonsTeacherFilter');
                sel.innerHTML = '<option value="">Все</option>'
                    + res.teachers.map(t => `<option value="${t.id}">${Adm.escapeHtml(t.full_name)}</option>`).join('');
                state.teachersLoaded = true;
            }

            document.getElementById('lessonsTotalBadge').textContent = res.total + ' записей';
            document.getElementById('tabCountLessons').textContent = res.total;

            renderTable(res.rows);
            Adm.renderPagination(
                document.getElementById('lessonsPagination'),
                res.page, res.pages,
                p => { state.page = p; load(true); window.scrollTo({top:0, behavior:'smooth'}); }
            );
        } catch (e) {
            wrap.innerHTML = Adm.emptyState('bi-exclamation-triangle', 'Ошибка', e.message);
        }
    }

    function renderTable(rows) {
        const wrap = document.getElementById('lessonsTableWrap');
        if (!rows.length) {
            wrap.innerHTML = Adm.emptyState('bi-calendar-x', 'Не найдено', 'Измените фильтры');
            return;
        }

        wrap.innerHTML = `
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th style="width:44px;">
                                <input type="checkbox" class="adm-checkbox" id="selectAllLessons"
                                       onchange="LessonsTab.toggleAll(this)">
                            </th>
                            <th style="width:110px;">Дата</th>
                            <th style="width:70px;" class="center">Пара</th>
                            <th>Дисциплина</th>
                            <th style="width:120px;">Группа</th>
                            <th style="width:180px;">Преподаватель</th>
                            <th class="center" style="width:80px;">Отметок</th>
                            <th class="center" style="width:80px;">Работ</th>
                            <th class="center" style="width:80px;">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rows.map(l => {
                            const isSelected = state.selected.has(l.id);
                            const rowCls = isSelected ? 'adm-row-selected' : '';
                            const time = l.lesson_time ? l.lesson_time.substring(0, 5) : '—';
                            const typeBadge = Adm.typeBadge(l.lesson_type);

                            return `
                                <tr class="${rowCls}" data-id="${l.id}">
                                    <td class="num">
                                        <input type="checkbox" class="adm-checkbox" data-lesson-id="${l.id}"
                                               ${isSelected ? 'checked' : ''}
                                               onchange="LessonsTab.toggle(${l.id}, this)">
                                    </td>
                                    <td>
                                        <div style="font-weight:600;font-size:0.85rem;">${Adm.fmtDate(l.lesson_date, 'date')}</div>
                                        <div style="font-size:0.72rem;color:var(--adm-text-soft);">${time}</div>
                                    </td>
                                    <td class="num">
                                        <span class="adm-status adm-status--type" style="font-weight:700;">
                                            ${l.lesson_number || '—'}
                                        </span>
                                    </td>
                                    <td>
                                        <div style="font-weight:600;font-size:0.85rem;margin-bottom:4px;">
                                            ${Adm.escapeHtml(l.discipline)}
                                        </div>
                                        <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                                            ${typeBadge}
                                            ${l.is_manual ? '<span class="adm-status adm-status--manual">Ручное</span>' : ''}
                                            ${l.audience ? `<span style="font-size:0.72rem;color:var(--adm-text-soft);">📍 ${Adm.escapeHtml(l.audience)}</span>` : ''}
                                            ${l.subgroup && l.subgroup !== '0' ? `<span style="font-size:0.72rem;color:var(--adm-text-soft);">подгр. ${Adm.escapeHtml(l.subgroup)}</span>` : ''}
                                        </div>
                                    </td>
                                    <td style="font-size:0.82rem;font-weight:600;">
                                        ${Adm.escapeHtml(l.group_name || '—')}
                                    </td>
                                    <td style="font-size:0.82rem;color:var(--adm-text-soft);">
                                        ${Adm.escapeHtml(l.teacher_name || '—')}
                                    </td>
                                    <td class="num">
                                        ${l.marks_cnt > 0
                                            ? `<span class="adm-status adm-status--active" style="font-weight:700;">${l.marks_cnt}</span>`
                                            : '<span style="color:var(--adm-text-soft);">—</span>'}
                                    </td>
                                    <td class="num">
                                        ${l.works_cnt > 0
                                            ? `<span class="adm-status adm-status--type" style="font-weight:700;">${l.works_cnt}</span>`
                                            : '<span style="color:var(--adm-text-soft);">—</span>'}
                                    </td>
                                    <td class="num">
                                        <div class="adm-row-actions">
                                            <button class="adm-icon-btn adm-icon-btn--danger"
                                                    onclick="LessonsTab.delete(${l.id}, '${Adm.escapeHtml(l.discipline).replace(/'/g, "\\'")}', '${Adm.fmtDate(l.lesson_date, 'date')}', ${l.marks_cnt}, ${l.works_cnt})"
                                                    title="Удалить">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            </div>
        `;

        updateBulkBar();
    }

    function updateBulkBar() {
        const count = state.selected.size;
        document.getElementById('lessonsBulkCount').textContent = count;
        document.getElementById('lessonsBulkBar').classList.toggle('show', count > 0);
    }

    const LessonsTab = {
        resetFilters() {
            state.search = '';
            state.teacherId = 0;
            state.dateFrom = '';
            state.dateTo = '';
            state.page = 1;
            document.getElementById('lessonsSearch').value = '';
            document.getElementById('lessonsTeacherFilter').value = '';
            document.getElementById('lessonsDateFrom').value = '';
            document.getElementById('lessonsDateTo').value = '';
            load(true);
        },
        toggle(id, cb) {
            if (cb.checked) state.selected.add(id);
            else state.selected.delete(id);
            cb.closest('tr').classList.toggle('adm-row-selected', cb.checked);
            updateBulkBar();
        },
        toggleAll(master) {
            document.querySelectorAll('.adm-checkbox[data-lesson-id]').forEach(cb => {
                const id = parseInt(cb.dataset.lessonId, 10);
                cb.checked = master.checked;
                cb.closest('tr').classList.toggle('adm-row-selected', master.checked);
                if (master.checked) state.selected.add(id);
                else state.selected.delete(id);
            });
            updateBulkBar();
        },
        clearSelection() {
            state.selected.clear();
            document.querySelectorAll('.adm-checkbox[data-lesson-id]').forEach(cb => {
                cb.checked = false;
                cb.closest('tr')?.classList.remove('adm-row-selected');
            });
            const master = document.getElementById('selectAllLessons');
            if (master) master.checked = false;
            updateBulkBar();
        },
        delete(id, discipline, date, marksCnt, worksCnt) {
            const warn = marksCnt + worksCnt > 0
                ? `\n\nВнимание: будет удалено ${marksCnt} отметок посещаемости и ${worksCnt} работ.`
                : '';
            Adm.confirmDialog(
                `Удалить занятие?\n\n${discipline}\n${date}${warn}`,
                async () => {
                    try {
                        await Adm.apiCall('lesson_delete', { id });
                        Adm.showToast('Занятие удалено');
                        load(true);
                    } catch (e) { Adm.showToast(e.message, true); }
                }
            );
        },
        bulkDelete() {
            const ids = Array.from(state.selected);
            if (!ids.length) return;
            Adm.confirmDialog(
                `Удалить ${ids.length} занятий?\n\nВсе отметки и работы будут удалены.`,
                async () => {
                    try {
                        await Adm.apiCall('lessons_delete_bulk', { ids });
                        Adm.showToast(`Удалено: ${ids.length}`);
                        LessonsTab.clearSelection();
                        load(true);
                    } catch (e) { Adm.showToast(e.message, true); }
                }
            );
        },
        openPurge() {
            Adm.openModal('lessonsPurgeModal');
        },
        applyPurge() {
            const date = document.getElementById('purgeDate').value;
            if (!date) return;
            Adm.confirmDialog(
                `Удалить ВСЕ занятия до ${date}?\n\nЭто действие необратимо.`,
                async () => {
                    Adm.closeModal('lessonsPurgeModal');
                    try {
                        const res = await Adm.apiCall('lessons_delete_old', { date });
                        Adm.showToast(`Удалено занятий: ${res.deleted}`);
                        load(true);
                    } catch (e) { Adm.showToast(e.message, true); }
                }
            );
        },
    };

    window.LessonsTab = LessonsTab;

    document.getElementById('lessonsSearch').addEventListener('input', Adm.debounce(e => {
        state.search = e.target.value.trim();
        state.page = 1;
        load(true);
    }, 300));

    document.getElementById('lessonsTeacherFilter').addEventListener('change', e => {
        state.teacherId = parseInt(e.target.value, 10) || 0;
        state.page = 1;
        load(true);
    });
    document.getElementById('lessonsDateFrom').addEventListener('change', e => {
        state.dateFrom = e.target.value;
        state.page = 1;
        load(true);
    });
    document.getElementById('lessonsDateTo').addEventListener('change', e => {
        state.dateTo = e.target.value;
        state.page = 1;
        load(true);
    });

    Adm.registerTab('lessons', {
        onInit: () => load(true),
    });
})();
</script>