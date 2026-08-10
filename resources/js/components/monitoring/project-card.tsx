import { Link } from '@inertiajs/react';
import { HeartbeatBadge } from '@/components/monitoring/heartbeat-badge';
import { useTranslations } from '@/hooks/use-translations';
import { cn } from '@/lib/utils';
import type { DashboardProject } from '@/types';

export function ProjectCard({ project }: { project: DashboardProject }) {
    const __ = useTranslations();

    return (
        <div
            className={cn(
                'flex flex-col gap-4 rounded-xl border p-4',
                project.heartbeat_status === 'stale' &&
                    'border-red-500 bg-red-50/60 dark:bg-red-950/30',
            )}
        >
            <div className="flex items-start justify-between gap-3">
                <div>
                    <h2 className="font-semibold">{project.name}</h2>
                    <p className="text-xs text-muted-foreground">
                        {project.environment}
                    </p>
                </div>

                <HeartbeatBadge
                    status={project.heartbeat_status}
                    lastHeartbeatAt={project.last_heartbeat_at}
                />
            </div>

            <Link
                href={`/issues?project=${project.id}&status=open`}
                className="text-sm hover:underline"
            >
                <span className="text-2xl font-semibold tabular-nums">
                    {project.open_issues_count}
                </span>{' '}
                <span className="text-muted-foreground">
                    {__('open issues')}
                </span>
            </Link>
        </div>
    );
}
