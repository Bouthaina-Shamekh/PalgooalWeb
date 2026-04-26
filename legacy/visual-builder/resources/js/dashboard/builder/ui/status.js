import { q } from '../helpers/dom';

export function setStatus(text, dotState = 'idle') {
    const root = q('#builder-save-status');
    if (!root) return;

    const textEl = root.querySelector('[data-status-text]');
    const timeEl = root.querySelector('[data-status-time]');
    const dotEl = root.querySelector('[data-status-dot]');

    if (textEl) textEl.textContent = text;

    if (timeEl) {
        const d = new Date();
        const hh = String(d.getHours()).padStart(2, '0');
        const mm = String(d.getMinutes()).padStart(2, '0');
        timeEl.textContent = `${hh}:${mm}`;
    }

    if (dotEl) {
        dotEl.classList.remove('bg-amber-400', 'bg-emerald-500', 'bg-red-500', 'bg-sky-500', 'animate-pulse');

        if (dotState === 'dirty') dotEl.classList.add('bg-amber-400', 'animate-pulse');
        else if (dotState === 'saving') dotEl.classList.add('bg-sky-500', 'animate-pulse');
        else if (dotState === 'saved') dotEl.classList.add('bg-emerald-500');
        else if (dotState === 'error') dotEl.classList.add('bg-red-500');
        else dotEl.classList.add('bg-amber-400');
    }
}
