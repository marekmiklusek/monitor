import { Head } from '@inertiajs/react';
import AppearanceTabs from '@/components/appearance-tabs';
import Heading from '@/components/heading';
import { useTranslations } from '@/hooks/use-translations';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { edit as editAppearance } from '@/routes/appearance';
import type { BreadcrumbItem } from '@/types';

export default function Appearance() {
    const __ = useTranslations();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: __('Appearance settings'),
            href: editAppearance(),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={__('Appearance settings')} />

            <h1 className="sr-only">{__('Appearance settings')}</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title={__('Appearance settings')}
                        description={__(
                            "Update your account's appearance settings",
                        )}
                    />
                    <AppearanceTabs />
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
