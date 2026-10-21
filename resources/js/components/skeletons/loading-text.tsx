/**
 * Pengganti skeleton untuk pembaca layar. Skeleton sendiri selalu
 * `aria-hidden`; area induknya diberi `aria-busy="true"` selama memuat.
 */
export function LoadingText({
    children = 'Memuat data...',
}: {
    children?: string;
}) {
    return <span className="sr-only">{children}</span>;
}
