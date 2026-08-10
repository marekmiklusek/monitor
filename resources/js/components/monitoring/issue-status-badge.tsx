import { useTranslations } from '@/hooks/use-translations';
import { cn } from '@/lib/utils';
import type { IssueStatus } from '@/types';

const LABELS: Record<IssueStatus, string> = {
    open: 'Open',
    resolved: 'Resolved',
    ignored: 'Ignored',
};

const STYLES: Record<IssueStatus, string> = {
    open: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300',
    resolved:
        'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
    ignored:
        'bg-neutral-200 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300',
};

export function IssueStatusBadge({ status }: { status: IssueStatus }) {
    const __ = useTranslations();

    return (
        <span
            className={cn(
                'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                STYLES[status],
            )}
        >
            {__(LABELS[status])}
        </span>
    );
}
