</main>

<footer class="text-center text-muted py-4 small">
    <div class="mb-2">
        <a href="https://аис-расписание.рф" class="text-decoration-none me-3">АИС-расписание.РФ</a>
        <a href="https://max.ru/se14203391_bot" target="_blank" rel="noopener" class="text-decoration-none">
            <i class="bi bi-send"></i> КузГТУшник в MAX
        </a>
    </div>
    <div style="opacity:0.6;">
        © <?= date('Y') ?> АИС-расписание.РФ
    </div>
</footer>

<?php
// ============================================================
// Очередь новых ачивок для показа (конфетти)
// ============================================================
$pendingCodes = $_SESSION['pending_achievements'] ?? [];

if (!empty($pendingCodes) && isset($pdo)) {
    // Подтянем данные ачивок
    $in = implode(',', array_fill(0, count($pendingCodes), '?'));
    $stmt = $pdo->prepare("SELECT code, title, description, points, icon, svg, color FROM achievements WHERE code IN ($in)");
    $stmt->execute($pendingCodes);
    $queue = $stmt->fetchAll();

    // Очищаем очередь — чтобы показать только один раз
    $_SESSION['pending_achievements'] = [];

    if (!empty($queue)): ?>

<!-- Модалка-конфетти -->
<div class="ach-celebrate-overlay" id="achCelebrateOverlay">
    <div class="ach-celebrate-modal" id="achCelebrateModal">
        <div class="ach-celebrate-icon" id="achCelebIcon">
            <i class="bi bi-trophy-fill"></i>
        </div>
        <div class="ach-celebrate-eyebrow">Новое достижение!</div>
        <div class="ach-celebrate-title" id="achCelebTitle"></div>
        <div class="ach-celebrate-desc" id="achCelebDesc"></div>
        <div class="ach-celebrate-points" id="achCelebPoints"></div>
        <button class="ach-celebrate-btn" onclick="achCelebClose()">Круто!</button>
    </div>
</div>

<script>
(function() {
    const queue = <?= json_encode($queue, JSON_UNESCAPED_UNICODE) ?>;
    let idx = 0;

    function showNext() {
        if (idx >= queue.length) return;
        const a = queue[idx++];

        const overlay = document.getElementById('achCelebrateOverlay');
        const iconEl  = document.getElementById('achCelebIcon');
        const titleEl = document.getElementById('achCelebTitle');
        const descEl  = document.getElementById('achCelebDesc');
        const pointsEl= document.getElementById('achCelebPoints');

        if (a.svg) {
            iconEl.innerHTML = a.svg;
        } else {
            iconEl.innerHTML = '<i class="bi ' + (a.icon || 'bi-trophy-fill') + '"></i>';
        }
        iconEl.style.background = 'linear-gradient(135deg, ' + (a.color || '#ff9f0a') + ', ' + shadeColor(a.color || '#ff9f0a', -30) + ')';
        iconEl.style.boxShadow = '0 20px 40px ' + hexToRgba(a.color || '#ff9f0a', 0.4);
        iconEl.querySelectorAll('svg').forEach(s => {
            s.style.width = '52px';
            s.style.height = '52px';
        });

        titleEl.textContent  = a.title;
        descEl.textContent   = a.description || '';
        pointsEl.textContent = '+' + a.points + ' очков';

        overlay.classList.add('show');
        launchConfetti();
    }

    window.achCelebClose = function() {
        const overlay = document.getElementById('achCelebrateOverlay');
        overlay.classList.remove('show');
        setTimeout(() => {
            if (idx < queue.length) showNext();
        }, 300);
    };

    function launchConfetti() {
        const colors = ['#ff453a', '#ff9f0a', '#ffd60a', '#30d158', '#5ac8fa', '#0071e3', '#bf5af2'];
        const shapes = ['square', 'circle', 'rect'];

        for (let i = 0; i < 60; i++) {
            const el = document.createElement('div');
            el.className = 'confetti-piece';

            const color = colors[Math.floor(Math.random() * colors.length)];
            const shape = shapes[Math.floor(Math.random() * shapes.length)];
            const size  = 6 + Math.random() * 10;
            const left  = Math.random() * 100;
            const delay = Math.random() * 0.6;
            const dur   = 2.5 + Math.random() * 1.5;

            el.style.left = left + '%';
            el.style.background = color;
            el.style.width = (shape === 'rect' ? size * 0.5 : size) + 'px';
            el.style.height = size + 'px';
            el.style.borderRadius = shape === 'circle' ? '50%' : (shape === 'square' ? '2px' : '1px');
            el.style.animationDelay = delay + 's';
            el.style.animationDuration = dur + 's';

            document.body.appendChild(el);
            setTimeout(() => el.remove(), (delay + dur) * 1000 + 200);
        }
    }

    function shadeColor(color, percent) {
        const num = parseInt(color.replace('#', ''), 16);
        const amt = Math.round(2.55 * percent);
        const R = Math.max(0, Math.min(255, (num >> 16) + amt));
        const G = Math.max(0, Math.min(255, ((num >> 8) & 0x00FF) + amt));
        const B = Math.max(0, Math.min(255, (num & 0x0000FF) + amt));
        return '#' + (0x1000000 + R * 0x10000 + G * 0x100 + B).toString(16).slice(1);
    }

    function hexToRgba(hex, alpha) {
        const num = parseInt(hex.replace('#', ''), 16);
        const R = (num >> 16) & 255;
        const G = (num >> 8) & 255;
        const B = num & 255;
        return `rgba(${R},${G},${B},${alpha})`;
    }

    if (queue.length > 0) {
        setTimeout(showNext, 600);
    }
})();
</script>

<?php endif;
} ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>