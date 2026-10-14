<?php
/**
 * Вкладка «Обзор» — статистика, график, топ должников, последние действия.
 */
?>

<!-- Скелетон -->
<div id="overviewSkeleton">
    <div class="adm-stats">
        <div class="adm-skeleton" style="grid-column: 1/-1; padding: 0;">
            <div class="adm-skeleton-row" style="height: 140px;"></div>
        </div>
    </div>
    <div class="adm-skeleton">
        <div class="adm-skeleton-row" style="height: 320px;"></div>
    </div>
</div>

<!-- Контент -->
<div id="overviewContent" style="display:none;">

    <!-- Карточки статистики -->
    <div class="adm-stats" id="overviewStats"></div>

    <!-- График + топ должников -->
    <div class="adm-overview-grid" style="display:grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px;">
        <div class="adm-card">
            <div class="adm-card-header">
                <h3 class="adm-card-title">
                    <i class="bi bi-graph-up-arrow"></i>
                    Посещаемость за 30 дней
                </h3>
                <span class="adm-card-badge">Динамика</span>
            </div>
            <div class="adm-card-body">
                <div style="position:relative; height:280px;">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>

        <div class="adm-card">
            <div class="adm-card-header">
                <h3 class="adm-card-title">
                    <i class="bi bi-exclamation-triangle-fill" style="color: var(--adm-red);"></i>
                    Топ должников
                </h3>
                <a href="../debtors.php" class="adm-card-badge">
                    Все <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="adm-card-body p-0" id="overviewDebtors"></div>
        </div>
    </div>

    <!-- Последние действия -->
    <div class="adm-card">
        <div class="adm-card-header">
            <h3 class="adm-card-title">
                <i class="bi bi-clock-history"></i>
                Последние действия
            </h3>
            <a href="#audit" class="adm-card-badge" onclick="event.preventDefault();Adm.showTab('audit');">
                Весь журнал <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        <div class="adm-card-body p-0" id="overviewAudit"></div>
    </div>

</div>

<style>
@media (max-width: 991px) {
    .adm-overview-grid { grid-template-columns: 1fr !important; }
}
</style>

<script>
(function() {
    'use strict';

    let trendChart = null;

    async function loadOverview() {
        const skeleton = document.getElementById('overviewSkeleton');
        const content  = document.getElementById('overviewContent');
        skeleton.style.display = 'block';
        content.style.display  = 'none';

        try {
            const [statsRes, trendRes, debtorsRes, auditRes] = await Promise.all([
                Adm.apiCall('stats_overview'),
                Adm.apiCall('attendance_trend', { days: 30 }),
                Adm.apiCall('top_debtors', { limit: 5 }),
                Adm.apiCall('audit_recent', { limit: 8 }),
            ]);

            renderStats(statsRes.stats);
            renderTrendChart(trendRes.labels, trendRes.values);
            renderDebtors(debtorsRes.rows);
            renderAudit(auditRes.rows);

            // Счётчики в табах
            const setTabCount = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.textContent = val;
            };
            setTabCount('tabCountUsers',    statsRes.stats.users_total);
            setTabCount('tabCountGroups',   statsRes.stats.groups_total);
            setTabCount('tabCountStudents', statsRes.stats.students_total);
            setTabCount('tabCountLessons',  statsRes.stats.lessons_total);
            setTabCount('tabCountResets',   statsRes.stats.reset_pending || '—');

            skeleton.style.display = 'none';
            content.style.display  = 'block';

            Adm.animateCounters(content);
            Adm.animateProgressBars(content);
        } catch (e) {
            skeleton.innerHTML = Adm.emptyState('bi-exclamation-triangle', 'Ошибка загрузки', e.message);
        }
    }

    function renderStats(s) {
        const attPct = s.att_pct ?? 0;
        const attCls = attPct >= 75 ? 'green' : (attPct >= 50 ? 'orange' : 'red');

        const cards = [
            {
                color: 'var(--adm-accent)', bg: 'rgba(0,113,227,0.1)',
                icon: 'bi-people-fill',
                value: s.users_total,
                label: 'Пользователей',
                sub: `Активных: <strong>${s.users_active}</strong> · Заблокировано: <strong>${s.users_blocked}</strong>`,
            },
            {
                color: 'var(--adm-green)', bg: 'rgba(48,209,88,0.1)',
                icon: 'bi-mortarboard-fill',
                value: s.students_total,
                label: 'Студентов',
                sub: `Групп: <strong>${s.groups_total}</strong>`,
            },
            {
                color: 'var(--adm-accent-2)', bg: 'rgba(94,92,230,0.1)',
                icon: 'bi-calendar-week-fill',
                value: s.lessons_total,
                label: 'Занятий всего',
                sub: `Сегодня: <strong>${s.lessons_today}</strong> · На неделе: <strong>${s.lessons_week}</strong>`,
            },
            {
                color: attPct >= 75 ? 'var(--adm-green)' : (attPct >= 50 ? 'var(--adm-orange)' : 'var(--adm-red)'),
                bg: attPct >= 75 ? 'rgba(48,209,88,0.1)' : (attPct >= 50 ? 'rgba(255,159,10,0.1)' : 'rgba(255,69,58,0.1)'),
                icon: 'bi-check-circle-fill',
                value: attPct,
                valueSuffix: '%',
                label: 'Посещаемость',
                sub: `За 30 дней · отметок: <strong>${Adm.fmtNumber(s.att_total)}</strong>`,
                progress: { value: attPct, cls: attCls },
            },
            {
                color: 'var(--adm-red)', bg: 'rgba(255,69,58,0.1)',
                icon: 'bi-exclamation-triangle-fill',
                value: s.debtors,
                label: 'Должников',
                sub: 'С пропусками ≥ 3 за 60 дней',
            },
            {
                color: 'var(--adm-orange)', bg: 'rgba(255,159,10,0.1)',
                icon: 'bi-key-fill',
                value: s.reset_pending,
                label: 'Запросов на сброс',
                sub: s.reset_pending > 0
                    ? `<a href="#resets" style="color:var(--adm-orange);font-weight:600;text-decoration:none;" onclick="event.preventDefault();Adm.showTab('resets');">Обработать →</a>`
                    : 'Все обработаны',
            },
        ];

        const html = cards.map(c => `
            <div class="adm-stat" style="--stat-color: ${c.color}; --stat-bg: ${c.bg}; --stat-glow: ${c.bg};">
                <div class="adm-stat-top">
                    <div class="adm-stat-icon"><i class="bi ${c.icon}"></i></div>
                </div>
                <div class="adm-stat-value"
                     data-count="${c.value}"
                     ${c.valueSuffix ? `data-count-suffix="${c.valueSuffix}"` : ''}>0</div>
                <div class="adm-stat-label">${c.label}</div>
                <div class="adm-stat-sub">${c.sub}</div>
                ${c.progress ? `
                    <div class="adm-progress">
                        <div class="adm-progress-bar">
                            <div class="adm-progress-fill ${c.progress.cls}" style="width: 0%;" data-width="${c.progress.value}"></div>
                        </div>
                        <span class="adm-progress-text">${c.progress.value}%</span>
                    </div>
                ` : ''}
            </div>
        `).join('');

        document.getElementById('overviewStats').innerHTML = html;
    }

    function renderTrendChart(labels, values) {
        const canvas = document.getElementById('trendChart');
        if (!canvas) return;
        if (typeof Chart === 'undefined') return;

        const ctx = canvas.getContext('2d');
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.05)';
        const textColor = isDark ? '#a1a1a6' : '#86868b';

        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(0,113,227,0.25)');
        gradient.addColorStop(1, 'rgba(0,113,227,0.02)');

        if (trendChart) trendChart.destroy();

        trendChart = new Chart(canvas, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Посещаемость %',
                    data: values,
                    borderColor: '#0071e3',
                    backgroundColor: gradient,
                    borderWidth: 3,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#0071e3',
                    pointBorderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 7,
                    tension: 0.35,
                    fill: true,
                    spanGaps: true,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(29,29,31,0.95)',
                        padding: 12,
                        cornerRadius: 10,
                        displayColors: false,
                        callbacks: {
                            label: ctx => ctx.parsed.y === null
                                ? 'нет данных'
                                : ctx.parsed.y + '% посещаемость'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true, max: 100,
                        ticks: { callback: v => v + '%', color: textColor },
                        grid: { color: gridColor, drawBorder: false }
                    },
                    x: {
                        ticks: { color: textColor, maxRotation: 0, autoSkip: true, maxTicksLimit: 10 },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    function renderDebtors(rows) {
        const wrap = document.getElementById('overviewDebtors');
        if (!rows.length) {
            wrap.innerHTML = Adm.emptyState('bi-check-circle-fill', 'Отлично!', 'Должников нет');
            return;
        }
        wrap.innerHTML = rows.map(r => `
            <a href="../debtors.php"
               style="display:flex;align-items:center;gap:12px;padding:12px 20px;text-decoration:none;color:inherit;border-bottom:1px solid #f5f5f7;transition:background 0.15s;"
               onmouseover="this.style.background='#fafafc'"
               onmouseout="this.style.background=''">
                <div class="adm-user-avatar red" style="width:36px;height:36px;font-size:0.75rem;">
                    ${Adm.escapeHtml((r.full_name || '?').charAt(0).toUpperCase())}
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:600;font-size:0.85rem;color:var(--adm-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        ${Adm.escapeHtml(r.full_name)}
                    </div>
                    <div style="font-size:0.72rem;color:var(--adm-text-soft);">
                        ${Adm.escapeHtml(r.group_name)}
                        ${r.zachetka ? '· ' + Adm.escapeHtml(r.zachetka) : ''}
                    </div>
                </div>
                <div style="text-align:right;flex-shrink:0;">
                    <div style="font-weight:700;color:var(--adm-red);font-size:1.05rem;">${r.absent}</div>
                    <div style="font-size:0.68rem;color:var(--adm-text-soft);">из ${r.total}</div>
                </div>
            </a>
        `).join('');
    }

    function renderAudit(rows) {
        const wrap = document.getElementById('overviewAudit');
        if (!rows.length) {
            wrap.innerHTML = Adm.emptyState('bi-clock-history', 'Пока нет записей');
            return;
        }
        wrap.innerHTML = rows.map(r => `
            <div style="display:flex;align-items:flex-start;gap:14px;padding:12px 24px;border-bottom:1px solid #f5f5f7;">
                <div style="flex-shrink:0;font-size:0.72rem;color:var(--adm-text-soft);min-width:90px;font-variant-numeric:tabular-nums;">
                    ${Adm.fmtDate(r.created_at, 'short')}
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:600;font-size:0.85rem;color:var(--adm-text);margin-bottom:2px;">
                        ${Adm.escapeHtml(r.user_name || '—')}
                    </div>
                    <div style="font-size:0.78rem;color:var(--adm-text-soft);">
                        ${Adm.escapeHtml(r.description || r.action)}
                    </div>
                </div>
            </div>
        `).join('');
    }

    Adm.registerTab('overview', {
        onInit: loadOverview,
        onShow: () => { /* данные уже загружены, повторно не грузим */ }
    });
})();
</script>