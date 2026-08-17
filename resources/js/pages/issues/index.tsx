import { Head, Link, router } from '@inertiajs/react';
import { IssueStatusBadge } from '@/components/monitoring/issue-status-badge';
import { Pagination } from '@/components/monitoring/pagination';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslations } from '@/hooks/use-translations';
import AppLayout from '@/layouts/app-layout';
import { isAfter, relativeTime } from '@/lib/relative-time';
import { cn } from '@/lib/utils';
import type {
    BreadcrumbItem,
    IssueFilters,
    IssueRow,
    OccurrenceType,
    Paginated,
    ProjectSummary,
} from '@/types';

const ALL_PROJECTS = 'all';

const TYPE_LABELS: Record<OccurrenceType, string> = {
    exception: 'Exception',
    failed_job: 'Failed job',
    slow_query: 'Slow query',
    log: 'Log',
};

const STATUS_TABS: Array<{ value: IssueFilters['status']; label: string }> = [
    { value: 'open', label: 'Open' },
    { value: 'resolved', label: 'Resolved' },
    { value: 'ignored', label: 'Ignored' },
    { value: 'all', label: 'All' },
];

function buildQuery(filters: IssueFilters, page?: number): string {
    const params = new URLSearchParams();

    params.set('status', filters.status);

    if (filters.project !== null) {
        params.set('project', filters.project);
    }

    if (page !== undefined && page > 1) {
        params.set('page', String(page));
    }

    return `/issues?${params.toString()}`;
}

export default function IssuesIndex({
    issues,
    projects,
    filters,
    recent_threshold,
}: {
    issues: Paginated<IssueRow>;
    projects: ProjectSummary[];
    filters: IssueFilters;
    recent_threshold: string;
}) {
    const __ = useTranslations();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: __('Issues'),
            href: '/issues',
        },
    ];

    const handleProjectChange = (project: string) => {
        router.get(
            buildQuery({
                status: filters.status,
                project: project === ALL_PROJECTS ? null : project,
            }),
            {},
            { preserveScroll: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={__('Issues')} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex gap-1 rounded-lg border p-1">
                        {STATUS_TABS.map((tab) => (
                            <Link
                                key={tab.value}
                                href={buildQuery({
                                    ...filters,
                                    status: tab.value,
                                })}
                                preserveScroll
                                className={cn(
                                    'rounded-md px-3 py-1 text-sm transition-colors',
                                    filters.status === tab.value
                                        ? 'bg-primary text-primary-foreground'
                                        : 'hover:bg-accent',
                                )}
                            >
                                {__(tab.label)}
                            </Link>
                        ))}
                    </div>

                    <Select
                        value={filters.project ?? ALL_PROJECTS}
                        onValueChange={handleProjectChange}
                    >
                        <SelectTrigger size="sm" className="w-48">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL_PROJECTS}>
                                {__('All projects')}
                            </SelectItem>
                            {projects.map((project) => (
                                <SelectItem key={project.id} value={project.id}>
                                    {project.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {issues.data.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        {__('No issues match these filters.')}
                    </p>
                ) : (
                    <div className="overflow-x-auto rounded-xl border">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-muted/40 text-xs text-muted-foreground uppercase">
                                <tr>
                                    <th className="px-3 py-2">
                                        {__('Project')}
                                    </th>
                                    <th className="px-3 py-2">{__('Type')}</th>
                                    <th className="px-3 py-2">{__('Issue')}</th>
                                    <th className="px-3 py-2 text-right">
                                        {__('Count')}
                                    </th>
                                    <th className="px-3 py-2">
                                        {__('Last seen')}
                                    </th>
                                    <th className="px-3 py-2">
                                        {__('Status')}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {issues.data.map((issue) => (
                                    <tr
                                        key={issue.id}
                                        onClick={() =>
                                            router.visit(`/issues/${issue.id}`)
                                        }
                                        className={cn(
                                            'cursor-pointer border-t transition-colors hover:bg-accent/50',
                                            isAfter(
                                                issue.last_seen_at,
                                                recent_threshold,
                                            ) &&
                                                'bg-amber-50 dark:bg-amber-950/30',
                                        )}
                                    >
                                        <td className="px-3 py-2 whitespace-nowrap">
                                            <span>{issue.project.name}</span>
                                            <span className="ml-1 text-xs text-muted-foreground">
                                                {issue.project.environment}
                                            </span>
                                        </td>
                                        <td className="px-3 py-2 text-xs whitespace-nowrap">
                                            {__(
                                                TYPE_LABELS[issue.type] ??
                                                    issue.type,
                                            )}
                                        </td>
                                        <td className="max-w-md px-3 py-2">
                                            <Link
                                                href={`/issues/${issue.id}`}
                                                onClick={(event) =>
                                                    event.stopPropagation()
                                                }
                                                className="font-medium"
                                            >
                                                {issue.title}
                                            </Link>
                                            {issue.message !== null && (
                                                <p className="truncate text-xs text-muted-foreground">
                                                    {issue.message}
                                                </p>
                                            )}
                                        </td>
                                        <td className="px-3 py-2 text-right tabular-nums">
                                            {issue.occurrences_count}
                                        </td>
                                        <td className="px-3 py-2 whitespace-nowrap text-muted-foreground">
                                            {relativeTime(
                                                issue.last_seen_at,
                                                __,
                                            )}
                                        </td>
                                        <td className="px-3 py-2">
                                            <IssueStatusBadge
                                                status={issue.status}
                                            />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <Pagination
                    currentPage={issues.current_page}
                    lastPage={issues.last_page}
                    buildUrl={(page) => buildQuery(filters, page)}
                />
            </div>
        </AppLayout>
    );
}
