import { cn } from '@/lib/utils';
import type { HeartbeatStatus } from '@/types';

const WAVE_PATH = 'M1 6h3.2l2.2-3.8L9 9.8l2.2-5.2L12.6 6H15';
const FLAT_PATH = 'M1 6h14';

export function HeartbeatPulse({
    status,
    className,
}: {
    status: HeartbeatStatus;
    className?: string;
}) {
    const isOk = status === 'ok';

    return (
        <svg
            viewBox="0 0 16 12"
            width="16"
            height="12"
            fill="none"
            stroke="currentColor"
            strokeWidth={1.75}
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
            className={cn('shrink-0', status === 'missing' && 'opacity-60')}
        >
            <path
                d={isOk ? WAVE_PATH : FLAT_PATH}
                className={cn(isOk && 'opacity-35', className)}
            />

            {isOk && <path d={WAVE_PATH} className="heartbeat-pulse-trace" />}
        </svg>
    );
}
