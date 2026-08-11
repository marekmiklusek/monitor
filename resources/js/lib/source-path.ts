const VENDOR_SEGMENTS = ['vendor', 'node_modules'];

export function isVendorPath(file: string): boolean {
    return /[\\/](vendor|node_modules)[\\/]/.test(file);
}

export function shortenPath(file: string): { path: string; name: string } {
    const parts = file.split(/[\\/]/);
    const name = parts.pop() ?? file;

    const vendorAt = parts.findIndex((part) => VENDOR_SEGMENTS.includes(part));

    const kept = vendorAt === -1 ? parts.slice(-2) : parts.slice(vendorAt + 1);

    return { path: kept.length === 0 ? '' : `${kept.join('/')}/`, name };
}

export function splitClassName(title: string): {
    namespace: string;
    name: string;
} {
    const separator = title.lastIndexOf('\\');

    if (separator === -1) {
        return { namespace: '', name: title };
    }

    return {
        namespace: title.slice(0, separator + 1),
        name: title.slice(separator + 1),
    };
}
