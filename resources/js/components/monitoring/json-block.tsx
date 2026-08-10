export function JsonBlock({ value }: { value: unknown }) {
    return (
        <pre className="overflow-x-auto rounded-lg border bg-muted/40 p-3 font-mono text-xs">
            {JSON.stringify(value, null, 2)}
        </pre>
    );
}
