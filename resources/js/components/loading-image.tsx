import { ImageOff } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { ImageSkeleton } from '@/components/skeletons/image-skeleton';
import { useDelayedFlag } from '@/hooks/use-delayed-flag';
import { cn } from '@/lib/utils';

type Props = {
    src: string;
    alt: string;
    /** Kelas untuk elemen img (mis. object-cover). */
    className?: string;
    /** Bingkai kecil (thumbnail): tampilan gagal hanya ikon. */
    compact?: boolean;
};

type Status = 'loading' | 'loaded' | 'failed';

/**
 * Foto dengan skeleton selama dimuat dan tampilan pengganti bila gagal.
 * Harus berada di dalam bingkai `relative` yang sudah berukuran/berasio
 * tetap (mis. aspect-video), agar skeleton menempati ruang yang sama.
 */
export function LoadingImage({ src, alt, className, compact = false }: Props) {
    const image = useRef<HTMLImageElement>(null);
    const [state, setState] = useState<{ src: string; status: Status }>({
        src,
        status: 'loading',
    });

    // Ganti foto (mis. setelah diunggah ulang): mulai memuat dari awal.
    if (state.src !== src) {
        setState({ src, status: 'loading' });
    }

    // Gambar dari cache bisa selesai sebelum React memasang onLoad (mis.
    // setelah render server), sehingga dicek langsung saat dipasang.
    useEffect(() => {
        const node = image.current;

        if (node?.complete) {
            setState({
                src,
                status: node.naturalWidth > 0 ? 'loaded' : 'failed',
            });
        }
    }, [src]);

    const showSkeleton = useDelayedFlag(state.status === 'loading');

    if (state.status === 'failed') {
        return (
            <span
                className="absolute inset-0 flex flex-col items-center justify-center gap-1 bg-muted p-1 text-center text-muted-foreground"
                role="img"
                aria-label={`${alt} (gagal dimuat)`}
            >
                <ImageOff className="size-4 shrink-0" aria-hidden="true" />
                {!compact && <span className="text-xs">Foto gagal dimuat</span>}
            </span>
        );
    }

    return (
        <>
            <img
                ref={image}
                src={src}
                alt={alt}
                onLoad={() => setState({ src, status: 'loaded' })}
                onError={() => setState({ src, status: 'failed' })}
                className={cn(
                    'bg-muted',
                    state.status === 'loading' && 'opacity-0',
                    className,
                )}
            />
            {showSkeleton && <ImageSkeleton />}
        </>
    );
}
