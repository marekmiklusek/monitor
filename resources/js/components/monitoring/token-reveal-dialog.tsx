import { useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useTranslations } from '@/hooks/use-translations';

async function copyToClipboard(
    text: string,
    fallback: HTMLTextAreaElement | null,
): Promise<boolean> {
    if (window.isSecureContext && navigator.clipboard) {
        try {
            await navigator.clipboard.writeText(text);

            return true;
        } catch {
            // Falls through to the selection based copy below.
        }
    }

    if (fallback === null) {
        return false;
    }

    fallback.focus();
    fallback.select();

    try {
        return document.execCommand('copy');
    } catch {
        return false;
    }
}

export function TokenRevealDialog({
    token,
    onClose,
}: {
    token: string | null;
    onClose: () => void;
}) {
    const __ = useTranslations();

    const [copied, setCopied] = useState(false);
    const [failed, setFailed] = useState(false);
    const tokenRef = useRef<HTMLTextAreaElement>(null);

    const handleCopy = async () => {
        if (token === null) {
            return;
        }

        const success = await copyToClipboard(token, tokenRef.current);

        setCopied(success);
        setFailed(!success);
    };

    const handleOpenChange = (open: boolean) => {
        if (!open) {
            setCopied(false);
            setFailed(false);
            onClose();
        }
    };

    return (
        <Dialog open={token !== null} onOpenChange={handleOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{__('Project token')}</DialogTitle>
                    <DialogDescription>
                        {__(
                            'Copy this token now. It is stored hashed and will never be shown again.',
                        )}
                    </DialogDescription>
                </DialogHeader>

                <textarea
                    ref={tokenRef}
                    readOnly
                    rows={2}
                    value={token ?? ''}
                    onFocus={(event) => event.target.select()}
                    className="w-full resize-none rounded-lg border bg-muted/40 p-3 font-mono text-xs break-all"
                />

                {failed && (
                    <p className="text-xs text-amber-600 dark:text-amber-400">
                        {__(
                            'Automatic copy is unavailable over plain HTTP. The token is selected — press Ctrl+C to copy it.',
                        )}
                    </p>
                )}

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={handleCopy}
                    >
                        {copied ? __('Copied') : __('Copy token')}
                    </Button>
                    <Button
                        type="button"
                        onClick={() => handleOpenChange(false)}
                    >
                        {__('Done')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
