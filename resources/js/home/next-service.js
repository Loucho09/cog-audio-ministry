// Marks the next (or live) service in the weekly timetable. Times are read from data-day (0 = Sunday)
// and data-time (24h HH:MM), always in Asia/Manila so the answer is the same for every visitor.
// data-live-minutes on #services sets how long a service counts as LIVE (default 90).
const ZONE = 'Asia/Manila';
const DEFAULT_LIVE_MINUTES = 90;
const REFRESH_MS = 60000;
const WEEK_MINUTES = 7 * 24 * 60;
const DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

const formatter = new Intl.DateTimeFormat('en-US', {
    timeZone: ZONE,
    weekday: 'short',
    hour: '2-digit',
    minute: '2-digit',
    hourCycle: 'h23',
});

function manilaNow() {
    const parts = Object.fromEntries(formatter.formatToParts(new Date()).map((p) => [p.type, p.value]));

    return DAYS.indexOf(parts.weekday) * 1440 + Number(parts.hour) * 60 + Number(parts.minute);
}

function update(slots, liveMinutes) {
    const now = manilaNow();
    let best = null;

    slots.forEach((slot) => {
        const [h, m] = slot.dataset.time.split(':').map(Number);
        const start = Number(slot.dataset.day) * 1440 + h * 60 + m;
        const sinceStart = (now - start + WEEK_MINUTES) % WEEK_MINUTES;
        const live = sinceStart < liveMinutes;
        const untilStart = (start - now + WEEK_MINUTES) % WEEK_MINUTES;
        const distance = live ? -1 : untilStart;

        slot.removeAttribute('data-state');
        if (best === null || distance < best.distance) {
            best = { slot, distance, live };
        }
    });

    if (!best) return;

    best.slot.dataset.state = best.live ? 'live' : 'next';
    const label = best.slot.querySelector('.js-led-label');
    if (label) label.textContent = best.live ? 'LIVE NOW' : 'NEXT';
}

export function initNextService() {
    const root = document.getElementById('services');
    if (!root) return;

    const slots = [...root.querySelectorAll('[data-day][data-time]')];
    if (slots.length === 0) return;

    const liveMinutes = Number(root.dataset.liveMinutes) || DEFAULT_LIVE_MINUTES;
    const refresh = () => update(slots, liveMinutes);
    let timer = 0;

    // Refreshes once a minute, only while the tab is visible. Coming back refreshes at once.
    const start = () => {
        if (timer || document.hidden) return;
        refresh();
        timer = window.setInterval(refresh, REFRESH_MS);
    };
    const stop = () => {
        window.clearInterval(timer);
        timer = 0;
    };

    document.addEventListener('visibilitychange', () => (document.hidden ? stop() : start()));
    // Back-forward cache: the page is frozen on pagehide, so the timer stops and restarts when it is restored.
    window.addEventListener('pagehide', stop);
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) start();
    });

    start();
}
