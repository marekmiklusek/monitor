const MINUTE = 60;
const HOUR = MINUTE * 60;
const DAY = HOUR * 24;

type Translator = (
    key: string,
    replacements?: Record<string, string | number>,
) => string;

const identity: Translator = (key, replacements) => {
    if (!replacements) {
        return key;
    }

    return Object.entries(replacements).reduce(
        (line, [token, value]) =>
            line.replace(new RegExp(`:${token}`, 'g'), String(value)),
        key,
    );
};

export function relativeTime(
    value: string | null,
    __: Translator = identity,
): string {
    if (value === null) {
        return __('never');
    }

    const timestamp = Date.parse(value);

    if (Number.isNaN(timestamp)) {
        return __('unknown');
    }

    const seconds = Math.round((Date.now() - timestamp) / 1000);

    if (seconds < 0) {
        return __('just now');
    }

    if (seconds < MINUTE) {
        return __(':count s ago', { count: seconds });
    }

    if (seconds < HOUR) {
        return __(':count min ago', { count: Math.floor(seconds / MINUTE) });
    }

    if (seconds < DAY) {
        return __(':count h ago', { count: Math.floor(seconds / HOUR) });
    }

    return __(':count d ago', { count: Math.floor(seconds / DAY) });
}

export function isAfter(value: string, threshold: string): boolean {
    return Date.parse(value) >= Date.parse(threshold);
}

export function absoluteTime(value: string | null, locale?: string): string {
    if (value === null) {
        return '—';
    }

    const timestamp = Date.parse(value);

    if (Number.isNaN(timestamp)) {
        return '—';
    }

    return new Date(timestamp).toLocaleString(locale ?? 'sk-SK', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
    });
}
