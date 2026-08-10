import { Head } from '@inertiajs/react';
import { ProjectCard } from '@/components/monitoring/project-card';
import { useTranslations } from '@/hooks/use-translations';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem, DashboardProject } from '@/types';

export default function Dashboard({
    projects,
}: {
    projects: DashboardProject[];
}) {
    const __ = useTranslations();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: __('Dashboard'),
            href: dashboard(),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={__('Dashboard')} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                {projects.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        {__(
                            'No projects yet. Create one to start receiving data.',
                        )}
                    </p>
                ) : (
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {projects.map((project) => (
                            <ProjectCard key={project.id} project={project} />
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
