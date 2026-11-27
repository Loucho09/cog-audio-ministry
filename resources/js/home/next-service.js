// Marks the next (or live) service in the weekly timetable. Times are read from data-day (0 = Sunday)
// and data-time (24h HH:MM), always in Asia/Manila so the answer is the same for every visitor.
const ZONE = 'Asia/Manila';
const LIVE_MINUTES = 90;
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

function update(slots) {
    const now = manilaNow();
    let best = null;

    slots.forEach((slot) => {
        const [h, m] = slot.dataset.time.split(':').map(Number);
        const start = Number(slot.dataset.day) * 1440 + h * 60 + m;
        const sinceStart = (now - start + WEEK_MINUTES) % WEEK_MINUTES;
        const live = sinceStart < LIVE_MINUTES;
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

    update(slots);
    window.setInterval(() => update(slots), 60000);
}
