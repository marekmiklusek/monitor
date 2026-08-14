import { router, usePage } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { cn } from '@/lib/utils';

export function FlashToast() {
    const { flash } = usePage().props;

    const [message, setMessage] = useState<string | null>(null);
    const [visible, setVisible] = useState(false);
    const timers = useRef<ReturnType<typeof setTimeout>[]>([]);

    useEffect(() => {
        const show = (value: unknown) => {
            if (typeof value !== 'string' || value === '') {
                return;
            }

            timers.current.forEach(clearTimeout);

            setMessage(value);
            setVisible(false);

            timers.current = [
                setTimeout(() => setVisible(true), 20),
                setTimeout(() => setVisible(false), 3000),
                setTimeout(() => setMessage(null), 3300),
            ];
        };

        show(flash);

        const stop = router.on('success', (event) => {
            show(event.detail.page.props.flash);
        });

        return () => {
            stop();
            timers.current.forEach(clearTimeout);
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    if (message === null) {
        return null;
    }

    return (
        <div className="pointer-events-none fixed inset-x-0 top-4 z-50 flex justify-center">
            <div
                role="status"
                aria-live="polite"
                className={cn(
                    'flex items-center gap-2 rounded-full border bg-background/95 px-4 py-2 text-sm font-medium shadow-lg backdrop-blur',
                    'transition-all duration-300 ease-out',
                    visible
                        ? 'translate-y-0 scale-100 opacity-100'
                        : '-translate-y-3 scale-95 opacity-0',
                )}
            >
                <CheckCircle2 className="size-4 text-emerald-600 dark:text-emerald-400" />
                {message}
            </div>
        </div>
    );
}
