import { useTranslations } from '@/hooks/use-translations';

function toFrames(stack: unknown): string[] {
    if (Array.isArray(stack)) {
        return stack.map((frame) =>
            typeof frame === 'string' ? frame : JSON.stringify(frame),
        );
    }

    if (typeof stack === 'string') {
        return stack.split('\n');
    }

    return [];
}

export function StackTrace({ stack }: { stack: unknown }) {
    const __ = useTranslations();

    const frames = toFrames(stack);

    if (frames.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                {__('No stack trace recorded.')}
            </p>
        );
    }

    return (
        <ol className="overflow-x-auto rounded-lg border bg-muted/40">
            {frames.map((frame, index) => (
                <li
                    key={`${index}-${frame}`}
                    className="flex gap-3 border-b px-3 py-1.5 font-mono text-xs last:border-b-0"
                >
                    <span className="shrink-0 text-muted-foreground select-none">
                        {index}
                    </span>
                    <span className="whitespace-pre">{frame}</span>
                </li>
            ))}
        </ol>
    );
}
