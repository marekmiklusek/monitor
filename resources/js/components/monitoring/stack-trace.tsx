import { ChevronDown, ChevronRight } from 'lucide-react';
import { Fragment, useState } from 'react';
import { useTranslations } from '@/hooks/use-translations';
import { isVendorPath, shortenPath } from '@/lib/source-path';
import { cn } from '@/lib/utils';

type Call = {
    class: string | null;
    function: string;
};

type Frame = {
    file: string | null;
    line: number | null;
    call: Call | null;
    vendor: boolean;
};

type Group = {
    vendor: boolean;
    frames: Array<{ frame: Frame; index: number }>;
};

function asString(value: unknown): string | null {
    return typeof value === 'string' && value !== '' ? value : null;
}

function toCall(record: Record<string, unknown>): Call | null {
    const fn = asString(record.function);

    if (fn === null) {
        return null;
    }

    return { class: asString(record.class), function: fn };
}

function safeParse(raw: string): Record<string, unknown> | null {
    try {
        const parsed: unknown = JSON.parse(raw);

        return typeof parsed === 'object' && parsed !== null
            ? (parsed as Record<string, unknown>)
            : null;
    } catch {
        return null;
    }
}

function parseFrame(raw: unknown): Frame {
    const record =
        typeof raw === 'string'
            ? (safeParse(raw) ?? { file: raw })
            : ((raw ?? {}) as Record<string, unknown>);

    const file = asString(record.file);

    return {
        file,
        line: typeof record.line === 'number' ? record.line : null,
        call: toCall(record),
        vendor: file !== null && isVendorPath(file),
    };
}

function toFrames(stack: unknown): Frame[] {
    if (Array.isArray(stack)) {
        return stack.map(parseFrame);
    }

    if (typeof stack === 'string') {
        return stack.split('\n').map(parseFrame);
    }

    return [];
}

function groupFrames(frames: Frame[]): Group[] {
    return frames.reduce<Group[]>((groups, frame, index) => {
        const last = groups.at(-1);

        if (last !== undefined && last.vendor === frame.vendor) {
            last.frames.push({ frame, index });

            return groups;
        }

        groups.push({ vendor: frame.vendor, frames: [{ frame, index }] });

        return groups;
    }, []);
}

function FrameRow({ frame, index }: { frame: Frame; index: number }) {
    const __ = useTranslations();

    const file = frame.file === null ? null : shortenPath(frame.file);

    return (
        <li
            className={cn(
                'flex gap-3 border-b px-3 py-2 text-xs last:border-b-0',
                frame.vendor
                    ? 'bg-muted/30'
                    : 'border-l-2 border-l-violet-500 bg-violet-50/40 dark:bg-violet-950/20',
            )}
        >
            <span className="w-5 shrink-0 pt-0.5 text-right font-mono text-muted-foreground select-none">
                {index}
            </span>

            <div className="min-w-0 flex-1 space-y-0.5">
                {frame.call !== null && (
                    <div
                        className={cn(
                            'font-mono break-all',
                            frame.vendor && 'opacity-60',
                        )}
                    >
                        {frame.call.class !== null && (
                            <>
                                <span className="text-sky-600 dark:text-sky-400">
                                    {frame.call.class}
                                </span>
                                <span className="text-muted-foreground">
                                    ::
                                </span>
                            </>
                        )}
                        <span className="font-medium text-violet-600 dark:text-violet-400">
                            {frame.call.function}
                        </span>
                        <span className="text-muted-foreground">()</span>
                    </div>
                )}

                {file !== null && (
                    <div
                        className={cn(
                            'font-mono break-all',
                            frame.vendor && 'opacity-60',
                        )}
                    >
                        <span className="text-muted-foreground">
                            {file.path}
                        </span>
                        <span className="text-foreground">{file.name}</span>
                        {frame.line !== null && (
                            <span className="text-amber-600 dark:text-amber-400">
                                :{frame.line}
                            </span>
                        )}
                    </div>
                )}
            </div>

            {frame.vendor && (
                <span className="shrink-0 self-start rounded bg-muted px-1.5 py-0.5 text-[10px] text-muted-foreground uppercase">
                    {__('vendor')}
                </span>
            )}
        </li>
    );
}

function VendorGroup({ group }: { group: Group }) {
    const __ = useTranslations();

    const [open, setOpen] = useState(false);

    if (group.frames.length < 3) {
        return (
            <>
                {group.frames.map(({ frame, index }) => (
                    <FrameRow key={index} frame={frame} index={index} />
                ))}
            </>
        );
    }

    return (
        <>
            <li className="border-b bg-muted/30 last:border-b-0">
                <button
                    type="button"
                    onClick={() => setOpen(!open)}
                    className="flex w-full items-center gap-1.5 px-3 py-2 text-xs text-muted-foreground transition-colors hover:bg-muted/60"
                >
                    {open ? (
                        <ChevronDown className="size-3.5" />
                    ) : (
                        <ChevronRight className="size-3.5" />
                    )}
                    {open
                        ? __('Hide :count vendor frames', {
                              count: group.frames.length,
                          })
                        : __('Show :count vendor frames', {
                              count: group.frames.length,
                          })}
                </button>
            </li>

            {open &&
                group.frames.map(({ frame, index }) => (
                    <FrameRow key={index} frame={frame} index={index} />
                ))}
        </>
    );
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

    const groups = groupFrames(frames);

    return (
        <ol className="overflow-hidden rounded-lg border">
            {groups.map((group, groupIndex) => (
                <Fragment key={groupIndex}>
                    {group.vendor ? (
                        <VendorGroup group={group} />
                    ) : (
                        group.frames.map(({ frame, index }) => (
                            <FrameRow key={index} frame={frame} index={index} />
                        ))
                    )}
                </Fragment>
            ))}
        </ol>
    );
}
