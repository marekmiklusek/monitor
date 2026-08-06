import { usePage } from '@inertiajs/react';

export type Replacements = Record<string, string | number>;

export function useTranslations() {
    const { translations } = usePage().props;

    return (key: string, replacements?: Replacements): string => {
        let line = translations[key] ?? key;

        if (replacements) {
            for (const [token, value] of Object.entries(replacements)) {
                line = line.replace(
                    new RegExp(`:${token}`, 'g'),
                    String(value),
                );
            }
        }

        return line;
    };
}
