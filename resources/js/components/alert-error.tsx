import { AlertCircleIcon } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { useTranslations } from '@/hooks/use-translations';

export default function AlertError({
    errors,
    title,
}: {
    errors: string[];
    title?: string;
}) {
    const __ = useTranslations();

    return (
        <Alert variant="destructive">
            <AlertCircleIcon />
            <AlertTitle>{title || __('Something went wrong.')}</AlertTitle>
            <AlertDescription>
                <ul className="list-inside list-disc text-sm">
                    {Array.from(new Set(errors)).map((error, index) => (
                        <li key={index}>{error}</li>
                    ))}
                </ul>
            </AlertDescription>
        </Alert>
    );
}
