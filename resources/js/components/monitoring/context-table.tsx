function toText(value: unknown): string {
    if (value === null || value === undefined) {
        return '—';
    }

    if (typeof value === 'string') {
        return value;
    }

    if (typeof value === 'number' || typeof value === 'boolean') {
        return String(value);
    }

    return JSON.stringify(value);
}

export function ContextTable({
    title,
    rows,
}: {
    title: string;
    rows: Record<string, unknown>;
}) {
    const entries = Object.entries(rows);

    if (entries.length === 0) {
        return null;
    }

    return (
        <section className="space-y-2">
            <h3 className="text-sm font-medium">{title}</h3>
            <div className="overflow-x-auto rounded-lg border">
                <table className="w-full text-left text-xs">
                    <tbody>
                        {entries.map(([key, value]) => (
                            <tr key={key} className="border-b last:border-b-0">
                                <th className="w-48 bg-muted/40 px-3 py-1.5 align-top font-medium">
                                    {key}
                                </th>
                                <td className="px-3 py-1.5 font-mono break-all">
                                    {toText(value)}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </section>
    );
}
