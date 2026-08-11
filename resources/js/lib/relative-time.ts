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

function pad(value: number): string {
    return String(value).padStart(2, '0');
}

export function absoluteTime(value: string | null): string {
    if (value === null) {
        return '—';
    }

    const timestamp = Date.parse(value);

    if (Number.isNaN(timestamp)) {
        return '—';
    }

    const date = new Date(timestamp);

    const day = pad(date.getDate());
    const month = pad(date.getMonth() + 1);
    const time = `${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`;

    return `${day}.${month}.${date.getFullYear()} ${time}`;
}
