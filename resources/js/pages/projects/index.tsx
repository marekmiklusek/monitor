import { Form, Head, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { HeartbeatBadge } from '@/components/monitoring/heartbeat-badge';
import { TokenRevealDialog } from '@/components/monitoring/token-reveal-dialog';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/hooks/use-translations';
import AppLayout from '@/layouts/app-layout';
import { absoluteTime } from '@/lib/relative-time';
import type { BreadcrumbItem, ProjectListItem } from '@/types';

export default function ProjectsIndex({
    projects,
    revealedToken,
}: {
    projects: ProjectListItem[];
    revealedToken: string | null;
}) {
    const __ = useTranslations();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: __('Projects'),
            href: '/projects',
        },
    ];

    const [revealed, setRevealed] = useState<string | null>(revealedToken);
    const [regenerating, setRegenerating] = useState<ProjectListItem | null>(
        null,
    );

    useEffect(() => {
        if (revealedToken !== null) {
            setRevealed(revealedToken);
        }
    }, [revealedToken]);

    const confirmRegenerate = () => {
        if (regenerating === null) {
            return;
        }

        router.post(
            `/projects/${regenerating.id}/token`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setRegenerating(null),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={__('Projects')} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="font-semibold">{__('New project')}</h2>

                    <Form
                        action="/projects"
                        method="post"
                        resetOnSuccess
                        className="flex flex-wrap items-start gap-3"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="space-y-1">
                                    <Label htmlFor="name">{__('Name')}</Label>
                                    <Input id="name" name="name" required />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="space-y-1">
                                    <Label htmlFor="environment">
                                        {__('Environment')}
                                    </Label>
                                    <Input
                                        id="environment"
                                        name="environment"
                                        defaultValue="production"
                                        required
                                    />
                                    <InputError message={errors.environment} />
                                </div>

                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="mt-6"
                                >
                                    {__('Create project')}
                                </Button>
                            </>
                        )}
                    </Form>
                </section>

                {projects.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        {__('No projects yet.')}
                    </p>
                ) : (
                    <div className="overflow-x-auto rounded-xl border">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-muted/40 text-xs text-muted-foreground uppercase">
                                <tr>
                                    <th className="px-3 py-2">{__('Name')}</th>
                                    <th className="px-3 py-2">
                                        {__('Environment')}
                                    </th>
                                    <th className="px-3 py-2">
                                        {__('Heartbeat')}
                                    </th>
                                    <th className="px-3 py-2">
                                        {__('Created')}
                                    </th>
                                    <th className="px-3 py-2 text-right">
                                        {__('Token')}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {projects.map((project) => (
                                    <tr key={project.id} className="border-t">
                                        <td className="px-3 py-2 font-medium">
                                            {project.name}
                                        </td>
                                        <td className="px-3 py-2 text-muted-foreground">
                                            {project.environment}
                                        </td>
                                        <td className="px-3 py-2">
                                            <HeartbeatBadge
                                                status={
                                                    project.heartbeat_status
                                                }
                                                lastHeartbeatAt={
                                                    project.last_heartbeat_at
                                                }
                                            />
                                        </td>
                                        <td className="px-3 py-2 whitespace-nowrap text-muted-foreground">
                                            {absoluteTime(project.created_at)}
                                        </td>
                                        <td className="px-3 py-2 text-right">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    setRegenerating(project)
                                                }
                                            >
                                                {__('Regenerate')}
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            <TokenRevealDialog
                token={revealed}
                onClose={() => setRevealed(null)}
            />

            <Dialog
                open={regenerating !== null}
                onOpenChange={(open) => !open && setRegenerating(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{__('Regenerate token')}</DialogTitle>
                        <DialogDescription>
                            {__(
                                'The current token for :name stops working immediately. Any client using it must be updated.',
                                { name: regenerating?.name ?? '' },
                            )}
                        </DialogDescription>
                    </DialogHeader>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setRegenerating(null)}
                        >
                            {__('Cancel')}
                        </Button>
                        <Button
                            type="button"
                            variant="destructive"
                            onClick={confirmRegenerate}
                        >
                            {__('Regenerate')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
