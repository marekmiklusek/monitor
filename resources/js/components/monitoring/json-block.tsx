import type { ReactNode } from 'react';

const TOKEN_PATTERN =
    /("(?:\\.|[^"\\])*")\s*:|("(?:\\.|[^"\\])*")|(-?\d+(?:\.\d+)?(?:[eE][+-]?\d+)?)|\b(true|false)\b|\b(null)\b/g;

function highlight(json: string): ReactNode[] {
    const nodes: ReactNode[] = [];

    let lastIndex = 0;
    let match: RegExpExecArray | null;
    let key = 0;

    TOKEN_PATTERN.lastIndex = 0;

    while ((match = TOKEN_PATTERN.exec(json)) !== null) {
        if (match.index > lastIndex) {
            nodes.push(json.slice(lastIndex, match.index));
        }

        const [full, propertyKey, string, number, boolean, nullish] = match;

        if (propertyKey !== undefined) {
            nodes.push(
                <span key={key++} className="text-sky-600 dark:text-sky-400">
                    {propertyKey}
                </span>,
                <span key={key++} className="text-muted-foreground">
                    :
                </span>,
            );
        } else if (string !== undefined) {
            nodes.push(
                <span
                    key={key++}
                    className="text-emerald-700 dark:text-emerald-400"
                >
                    {string}
                </span>,
            );
        } else if (number !== undefined) {
            nodes.push(
                <span
                    key={key++}
                    className="text-amber-600 dark:text-amber-400"
                >
                    {number}
                </span>,
            );
        } else if (boolean !== undefined) {
            nodes.push(
                <span
                    key={key++}
                    className="text-violet-600 dark:text-violet-400"
                >
                    {boolean}
                </span>,
            );
        } else if (nullish !== undefined) {
            nodes.push(
                <span key={key++} className="text-muted-foreground italic">
                    {nullish}
                </span>,
            );
        }

        lastIndex = match.index + full.length;
    }

    if (lastIndex < json.length) {
        nodes.push(json.slice(lastIndex));
    }

    return nodes;
}

export function JsonBlock({ value }: { value: unknown }) {
    return (
        <pre className="overflow-x-auto rounded-lg border bg-muted/40 p-3 font-mono text-xs wrap-anywhere whitespace-pre-wrap">
            {highlight(JSON.stringify(value, null, 2))}
        </pre>
    );
}
