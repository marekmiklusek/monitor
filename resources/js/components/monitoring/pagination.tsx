import { Link } from '@inertiajs/react';
import { useTranslations } from '@/hooks/use-translations';
import { cn } from '@/lib/utils';

export function Pagination({
    currentPage,
    lastPage,
    buildUrl,
}: {
    currentPage: number;
    lastPage: number;
    buildUrl: (page: number) => string;
}) {
    const __ = useTranslations();

    if (lastPage <= 1) {
        return null;
    }

    const linkClass =
        'rounded-md border px-3 py-1.5 text-sm transition-colors hover:bg-accent';

    return (
        <nav className="flex items-center justify-between gap-4">
            <div className="text-sm text-muted-foreground">
                {__('Page :current of :last', {
                    current: currentPage,
                    last: lastPage,
                })}
            </div>

            <div className="flex gap-2">
                {currentPage > 1 ? (
                    <Link
                        href={buildUrl(currentPage - 1)}
                        className={linkClass}
                        preserveScroll
                    >
                        {__('Previous')}
                    </Link>
                ) : (
                    <span className={cn(linkClass, 'opacity-40')}>
                        {__('Previous')}
                    </span>
                )}

                {currentPage < lastPage ? (
                    <Link
                        href={buildUrl(currentPage + 1)}
                        className={linkClass}
                        preserveScroll
                    >
                        {__('Next')}
                    </Link>
                ) : (
                    <span className={cn(linkClass, 'opacity-40')}>
                        {__('Next')}
                    </span>
                )}
            </div>
        </nav>
    );
}
