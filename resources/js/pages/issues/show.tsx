import { Head, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { IssueStatusBadge } from '@/components/monitoring/issue-status-badge';
import { OccurrenceDetail } from '@/components/monitoring/occurrence-detail';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/hooks/use-translations';
import AppLayout from '@/layouts/app-layout';
import { absoluteTime, relativeTime } from '@/lib/relative-time';
import { shortenPath, splitClassName } from '@/lib/source-path';
import { cn } from '@/lib/utils';
import type {
    BreadcrumbItem,
    IssueDetail,
    IssueStatus,
    OccurrenceDetail as Detail,
    OccurrenceSummary,
} from '@/types';

const ACTIONS: Array<{ label: string; status: IssueStatus }> = [
    { label: 'Resolve', status: 'resolved' },
    { label: 'Ignore', status: 'ignored' },
    { label: 'Reopen', status: 'open' },
];

export default function IssueShow({
    issue,
    occurrences,
}: {
    issue: IssueDetail;
    occurrences: OccurrenceSummary[];
}) {
    const __ = useTranslations();

    const [selectedId, setSelectedId] = useState<string | null>(
        occurrences[0]?.id ?? null,
    );
    const [loaded, setLoaded] = useState<Detail | null>(null);
    const [failedId, setFailedId] = useState<string | null>(null);

    const detail = loaded !== null && loaded.id === selectedId ? loaded : null;
    const loading =
        selectedId !== null && detail === null && failedId !== selectedId;

    useEffect(() => {
        if (selectedId === null) {
            return;
        }

        const controller = new AbortController();

        fetch(`/issues/${issue.id}/occurrences/${selectedId}`, {
            headers: { Accept: 'application/json' },
            signal: controller.signal,
        })
            .then((response) => response.json())
            .then((data: Detail) => {
                setLoaded(data);
            })
            .catch(() => {
                if (!controller.signal.aborted) {
                    setFailedId(selectedId);
                }
            });

        return () => controller.abort();
    }, [issue.id, selectedId]);

    const title = splitClassName(issue.title);

    const location = issue.file === null ? null : shortenPath(issue.file);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: __('Issues'), href: '/issues' },
        { title: title.name, href: `/issues/${issue.id}` },
    ];

    const changeStatus = (status: IssueStatus) => {
        router.post(
            `/issues/${issue.id}/status`,
            { status },
            { preserveScroll: status === 'open' },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={issue.title} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <header className="space-y-3 rounded-xl border p-4">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <div className="min-w-0 space-y-1">
                            {title.namespace !== '' && (
                                <p className="truncate font-mono text-xs text-muted-foreground">
                                    {title.namespace}
                                </p>
                            )}

                            <div className="flex flex-wrap items-center gap-2">
                                <h1 className="text-lg font-semibold">
                                    {title.name}
                                </h1>
                                <IssueStatusBadge status={issue.status} />
                            </div>
                        </div>

                        <div className="flex gap-2">
                            {ACTIONS.filter(
                                (action) => action.status !== issue.status,
                            ).map((action) => (
                                <Button
                                    key={action.status}
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => changeStatus(action.status)}
                                >
                                    {__(action.label)}
                                </Button>
                            ))}
                        </div>
                    </div>

                    {issue.message !== null && (
                        <p className="rounded-r-md border-l-4 border-destructive bg-destructive/10 py-2 pr-3 pl-4 font-mono text-sm wrap-break-word text-destructive-foreground">
                            {issue.message}
                        </p>
                    )}

                    <dl className="grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <dt className="text-xs text-muted-foreground">
                                {__('Project')}
                            </dt>
                            <dd>
                                {issue.project.name}{' '}
                                <span className="text-muted-foreground">
                                    {issue.project.environment}
                                </span>
                            </dd>
                        </div>

                        {location !== null && (
                            <div>
                                <dt className="text-xs text-muted-foreground">
                                    {__('Location')}
                                </dt>
                                <dd className="font-mono text-xs break-all">
                                    <span className="text-muted-foreground">
                                        {location.path}
                                    </span>
                                    {location.name}
                                    {issue.line !== null && (
                                        <span className="text-amber-600 dark:text-amber-400">
                                            :{issue.line}
                                        </span>
                                    )}
                                </dd>
                            </div>
                        )}

                        <div>
                            <dt className="text-xs text-muted-foreground">
                                {__('Occurrences')}
                            </dt>
                            <dd className="tabular-nums">
                                {issue.occurrences_count}
                            </dd>
                        </div>

                        <div>
                            <dt className="text-xs text-muted-foreground">
                                {__('Seen')}
                            </dt>
                            <dd>
                                {relativeTime(issue.last_seen_at, __)}
                                <span className="text-muted-foreground">
                                    {' '}
                                    ·{' '}
                                    {__('first :date', {
                                        date: absoluteTime(issue.first_seen_at),
                                    })}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </header>

                <div className="grid gap-4 lg:grid-cols-[16rem_minmax(0,1fr)]">
                    <aside className="space-y-2">
                        <h2 className="text-sm font-medium">
                            {__('Recent occurrences')}
                        </h2>

                        {occurrences.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                {__('No occurrences stored.')}
                            </p>
                        ) : (
                            <ul className="overflow-hidden rounded-lg border">
                                {occurrences.map((occurrence) => (
                                    <li key={occurrence.id}>
                                        <button
                                            type="button"
                                            onClick={() =>
                                                setSelectedId(occurrence.id)
                                            }
                                            className={cn(
                                                'w-full border-b px-3 py-2 text-left text-xs transition-colors last:border-b-0 hover:bg-accent',
                                                selectedId === occurrence.id &&
                                                    'bg-accent font-medium',
                                            )}
                                        >
                                            {absoluteTime(
                                                occurrence.occurred_at,
                                            )}
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </aside>

                    <section className="min-w-0 rounded-xl border p-4">
                        {loading && (
                            <p className="text-sm text-muted-foreground">
                                {__('Loading occurrence…')}
                            </p>
                        )}

                        {!loading && detail === null && (
                            <p className="text-sm text-muted-foreground">
                                {occurrences.length === 0
                                    ? __('No occurrences stored.')
                                    : __('Select an occurrence to inspect it.')}
                            </p>
                        )}

                        {!loading && detail !== null && (
                            <OccurrenceDetail occurrence={detail} />
                        )}
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}
