import { useTranslations } from '@/hooks/use-translations';
import { relativeTime } from '@/lib/relative-time';
import { cn } from '@/lib/utils';
import type { HeartbeatStatus } from '@/types';

const LABELS: Record<HeartbeatStatus, string> = {
    ok: 'OK',
    stale: 'Stale',
    missing: 'No heartbeat',
};

const STYLES: Record<HeartbeatStatus, string> = {
    ok: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
    stale: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300',
    missing:
        'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-300',
};

export function HeartbeatBadge({
    status,
    lastHeartbeatAt,
}: {
    status: HeartbeatStatus;
    lastHeartbeatAt: string | null;
}) {
    const __ = useTranslations();

    return (
        <span
            className={cn(
                'inline-flex items-center gap-2 rounded-full px-2.5 py-1 text-xs font-medium',
                STYLES[status],
            )}
        >
            <span
                className={cn(
                    'size-1.5 rounded-full',
                    status === 'ok' && 'bg-emerald-600',
                    status === 'stale' && 'animate-pulse bg-red-600',
                    status === 'missing' && 'bg-amber-600',
                )}
            />
            {__(LABELS[status])}
            {status !== 'missing' && (
                <span className="opacity-70">
                    {relativeTime(lastHeartbeatAt, __)}
                </span>
            )}
        </span>
    );
}
