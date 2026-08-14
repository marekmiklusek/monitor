import { ChevronDown, ChevronRight } from 'lucide-react';
import { useState } from 'react';
import { ContextTable } from '@/components/monitoring/context-table';
import { JsonBlock } from '@/components/monitoring/json-block';
import { hasFrames, StackTrace } from '@/components/monitoring/stack-trace';
import { useTranslations } from '@/hooks/use-translations';
import { absoluteTime } from '@/lib/relative-time';
import { cn } from '@/lib/utils';
import type { Breadcrumb, OccurrenceDetail as Detail } from '@/types';

const KNOWN_CONTEXT_KEYS = ['url', 'method', 'input', 'headers'];

function asRecord(value: unknown): Record<string, unknown> {
    return value !== null && typeof value === 'object' && !Array.isArray(value)
        ? (value as Record<string, unknown>)
        : {};
}

function BreadcrumbRow({ breadcrumb }: { breadcrumb: Breadcrumb }) {
    const [open, setOpen] = useState(false);

    const context = asRecord(breadcrumb.context);
    const expandable = Object.keys(context).length > 0;

    return (
        <>
            <tr
                onClick={expandable ? () => setOpen(!open) : undefined}
                className={cn(
                    'border-t',
                    expandable && 'cursor-pointer hover:bg-accent/50',
                )}
            >
                <td className="w-6 pl-3 text-muted-foreground">
                    {expandable &&
                        (open ? (
                            <ChevronDown className="size-3.5" />
                        ) : (
                            <ChevronRight className="size-3.5" />
                        ))}
                </td>
                <td className="px-3 py-1.5 font-mono">{breadcrumb.level}</td>
                <td className="px-3 py-1.5">{breadcrumb.message}</td>
                <td className="px-3 py-1.5 whitespace-nowrap text-muted-foreground">
                    {absoluteTime(breadcrumb.logged_at)}
                </td>
            </tr>

            {expandable && open && (
                <tr className="border-t bg-muted/20">
                    <td colSpan={4} className="p-3">
                        <JsonBlock value={context} />
                    </td>
                </tr>
            )}
        </>
    );
}

export function OccurrenceDetail({ occurrence }: { occurrence: Detail }) {
    const __ = useTranslations();

    const context = asRecord(occurrence.payload.context);

    const request = Object.fromEntries(
        Object.entries(context).filter(
            ([key]) => key === 'url' || key === 'method',
        ),
    );

    const input = asRecord(context.input);
    const headers = asRecord(context.headers);

    const rest = Object.fromEntries(
        Object.entries(context).filter(
            ([key]) => !KNOWN_CONTEXT_KEYS.includes(key),
        ),
    );

    return (
        <div className="space-y-6">
            <div className="flex items-center gap-2 border-b pb-3 text-sm">
                <span className="text-muted-foreground">
                    {__('Occurred at')}
                </span>
                <span className="font-mono">
                    {absoluteTime(occurrence.occurred_at)}
                </span>
            </div>

            {hasFrames(occurrence.payload.stack) && (
                <section className="space-y-2">
                    <h3 className="text-sm font-medium">{__('Stack trace')}</h3>
                    <StackTrace stack={occurrence.payload.stack} />
                </section>
            )}

            <ContextTable title={__('Request')} rows={request} />
            <ContextTable title={__('Input')} rows={input} />
            <ContextTable title={__('Headers')} rows={headers} />

            {Object.keys(rest).length > 0 && (
                <section className="space-y-2">
                    <h3 className="text-sm font-medium">{__('Context')}</h3>
                    <JsonBlock value={rest} />
                </section>
            )}

            {Array.isArray(occurrence.payload.breadcrumbs) &&
                occurrence.payload.breadcrumbs.length > 0 && (
                    <section className="space-y-2">
                        <h3 className="text-sm font-medium">
                            {__('Breadcrumbs')}
                        </h3>
                        <div className="overflow-x-auto rounded-lg border">
                            <table className="w-full text-left text-xs">
                                <thead className="bg-muted/40">
                                    <tr>
                                        <th className="w-6 pl-3" />
                                        <th className="px-3 py-1.5">
                                            {__('Level')}
                                        </th>
                                        <th className="px-3 py-1.5">
                                            {__('Message')}
                                        </th>
                                        <th className="px-3 py-1.5">
                                            {__('Logged at')}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {occurrence.payload.breadcrumbs.map(
                                        (breadcrumb, index) => (
                                            <BreadcrumbRow
                                                key={index}
                                                breadcrumb={breadcrumb}
                                            />
                                        ),
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </section>
                )}
        </div>
    );
}
