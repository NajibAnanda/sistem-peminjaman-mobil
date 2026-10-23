import { Head, Link } from '@inertiajs/react';
import { CarFront, ChevronLeft, ChevronRight } from 'lucide-react';
import type { CSSProperties } from 'react';
import { useMemo, useState } from 'react';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import {
    LANE_HEIGHT_PX,
    ROW_PADDING_PX,
    ScheduleBlock,
} from '@/components/schedule/schedule-block';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { LoadingText } from '@/components/skeletons/loading-text';
import { Card } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { useDateFormat } from '@/hooks/use-date-format';
import { useDelayedFlag } from '@/hooks/use-delayed-flag';
import { layoutTimeline, nowPosition } from '@/lib/timeline';
import { cn } from '@/lib/utils';
import { index } from '@/routes/schedule';
import type { BookingDisplayStatus, ScheduleProps } from '@/types';

const LEGEND: BookingDisplayStatus[] = [
    'scheduled',
    'not_picked_up',
    'active',
    'overdue',
    'completed',
];

// Kolom mobil + 7 kolom hari; lebar minimum agar tetap terbaca dan tabel
// bergeser horizontal di layar sempit.
const GRID: CSSProperties = {
    gridTemplateColumns: '11rem repeat(7, minmax(6.5rem, 1fr))',
};

export default function ScheduleIndex({
    now,
    week,
    cars,
    bookings,
}: ScheduleProps) {
    const { formatDate, formatWeekday, toDateTimeLocal } = useDateFormat();
    const rows = useMemo(
        () => layoutTimeline(bookings, week.start, week.end),
        [bookings, week.start, week.end],
    );
    const nowLeft = useMemo(
        () => nowPosition(now, week.start, week.end),
        [now, week.start, week.end],
    );
    const today = toDateTimeLocal(now).slice(0, 10);
    const lastDay = week.days[week.days.length - 1];
    const weekLabel = `${formatDate(week.start)} – ${formatDate(`${lastDay}T12:00:00Z`)}`;
    // Saat berpindah minggu, blok jadwal diganti skeleton (kolom mobil dan
    // header tetap) bila pemuatan lebih lama dari jeda anti-kedip.
    const [navigating, setNavigating] = useState(false);
    const showSkeleton = useDelayedFlag(navigating);
    const trackNavigation = {
        onStart: () => setNavigating(true),
        onFinish: () => setNavigating(false),
    };

    return (
        <>
            <Head title="Jadwal Armada" />

            <div className="flex min-w-0 flex-1 flex-col gap-4 p-4">
                <PageHeader
                    title="Jadwal Armada"
                    description="Lihat pemakaian setiap mobil dalam sepekan dan klik blok jadwal untuk membuka detail peminjaman."
                    actions={
                        <div className="flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="icon"
                                asChild
                                aria-label="Minggu sebelumnya"
                            >
                                <Link
                                    href={index({
                                        query: { week: week.previous },
                                    })}
                                    preserveScroll
                                    {...trackNavigation}
                                >
                                    <ChevronLeft aria-hidden="true" />
                                </Link>
                            </Button>
                            <Button
                                variant={
                                    week.is_current ? 'secondary' : 'outline'
                                }
                                asChild
                            >
                                <Link
                                    href={index()}
                                    preserveScroll
                                    {...trackNavigation}
                                >
                                    Minggu ini
                                </Link>
                            </Button>
                            <Button
                                variant="outline"
                                size="icon"
                                asChild
                                aria-label="Minggu berikutnya"
                            >
                                <Link
                                    href={index({ query: { week: week.next } })}
                                    preserveScroll
                                    {...trackNavigation}
                                >
                                    <ChevronRight aria-hidden="true" />
                                </Link>
                            </Button>
                        </div>
                    }
                />

                <Card className="min-w-0 gap-0 py-0">
                    <div className="flex flex-col gap-3 border-b px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 className="font-semibold tabular-nums">
                                {weekLabel}
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                {bookings.length > 0
                                    ? `${bookings.length} peminjaman minggu ini`
                                    : 'Tidak ada peminjaman minggu ini'}
                            </p>
                        </div>
                        <ul
                            className="flex flex-wrap gap-1.5"
                            aria-label="Keterangan warna"
                        >
                            {LEGEND.map((status) => (
                                <li key={status}>
                                    <StatusBadge status={status} />
                                </li>
                            ))}
                        </ul>
                    </div>

                    {cars.length === 0 ? (
                        <EmptyState
                            icon={CarFront}
                            title="Belum ada mobil aktif"
                            description="Tambahkan mobil di menu Data Mobil agar jadwalnya tampil di sini."
                        />
                    ) : (
                        <div className="overflow-x-auto" aria-busy={navigating}>
                            {showSkeleton && <LoadingText />}
                            <div
                                className="grid min-w-max"
                                style={GRID}
                                role="table"
                                aria-label={`Jadwal armada ${weekLabel}`}
                            >
                                <div className="contents" role="row">
                                    <div
                                        className="sticky left-0 z-20 border-b bg-card px-6 py-2 text-xs font-medium text-muted-foreground"
                                        role="columnheader"
                                    >
                                        Mobil
                                    </div>
                                    {week.days.map((day) => (
                                        <div
                                            key={day}
                                            className={cn(
                                                'border-b border-l px-2 py-2 text-center text-xs font-medium',
                                                day === today
                                                    ? 'bg-primary/10 text-primary'
                                                    : 'text-muted-foreground',
                                            )}
                                            role="columnheader"
                                            aria-current={
                                                day === today
                                                    ? 'date'
                                                    : undefined
                                            }
                                        >
                                            {formatWeekday(day)}
                                        </div>
                                    ))}
                                </div>

                                {cars.map((car, carIndex) => {
                                    const row = rows.get(car.id);
                                    const lanes = Math.max(row?.lanes ?? 0, 1);
                                    const height =
                                        lanes * LANE_HEIGHT_PX +
                                        ROW_PADDING_PX * 2;
                                    // Baris terakhir tanpa garis bawah: tepi card
                                    // sudah menjadi garisnya (tidak dobel).
                                    const rowBorder =
                                        carIndex < cars.length - 1 &&
                                        'border-b';

                                    return (
                                        <div
                                            key={car.id}
                                            className="contents"
                                            role="row"
                                        >
                                            <div
                                                className={cn(
                                                    'sticky left-0 z-10 grid content-center gap-0.5 bg-card px-6 py-1.5',
                                                    rowBorder,
                                                )}
                                                role="rowheader"
                                            >
                                                <span className="truncate text-sm font-medium">
                                                    {car.plate_number}
                                                </span>
                                                <span className="truncate text-xs text-muted-foreground">
                                                    {car.brand} {car.model}
                                                </span>
                                            </div>
                                            <div
                                                // minHeight, bukan height: kedua sel
                                                // meregang ke tinggi baris yang sama
                                                // sehingga garis bawahnya sejajar.
                                                className={cn(
                                                    'relative col-span-7',
                                                    rowBorder,
                                                )}
                                                style={{ minHeight: height }}
                                                role="cell"
                                            >
                                                <DayGrid
                                                    days={week.days}
                                                    today={today}
                                                />
                                                {showSkeleton ? (
                                                    <BlockSkeleton
                                                        index={carIndex}
                                                    />
                                                ) : (
                                                    <>
                                                        {nowLeft !== null && (
                                                            <div
                                                                className="pointer-events-none absolute inset-y-0 z-1 w-px bg-destructive/70"
                                                                style={{
                                                                    left: `${nowLeft}%`,
                                                                }}
                                                                aria-hidden="true"
                                                            />
                                                        )}
                                                        {row?.blocks.map(
                                                            (block) => (
                                                                <ScheduleBlock
                                                                    key={
                                                                        block
                                                                            .booking
                                                                            .id
                                                                    }
                                                                    block={
                                                                        block
                                                                    }
                                                                />
                                                            ),
                                                        )}
                                                    </>
                                                )}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    )}
                </Card>
            </div>
        </>
    );
}

// Posisi & lebar blok skeleton berselang-seling per baris agar tampak
// seperti jadwal, tanpa bergantung pada data minggu yang sedang dimuat.
const SKELETON_BLOCKS = [
    { left: '4%', width: '30%' },
    { left: '38%', width: '22%' },
    { left: '18%', width: '40%' },
    { left: '58%', width: '28%' },
];

function BlockSkeleton({ index }: { index: number }) {
    const block = SKELETON_BLOCKS[index % SKELETON_BLOCKS.length];

    return (
        <Skeleton
            className="absolute"
            style={{
                top: ROW_PADDING_PX,
                height: LANE_HEIGHT_PX - ROW_PADDING_PX,
                ...block,
            }}
            aria-hidden="true"
        />
    );
}

/** Garis pemisah hari dan sorotan hari ini di belakang blok. */
function DayGrid({ days, today }: { days: string[]; today: string }) {
    return (
        <div
            className="pointer-events-none absolute inset-0 grid grid-cols-7"
            aria-hidden="true"
        >
            {days.map((day) => (
                <div
                    key={day}
                    className={cn('border-l', day === today && 'bg-primary/5')}
                />
            ))}
        </div>
    );
}

ScheduleIndex.layout = {
    breadcrumbs: [{ title: 'Jadwal Armada', href: index() }],
};
