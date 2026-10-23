import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { CircleAlert } from 'lucide-react';
import type { FormEvent } from 'react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { DateTimePicker } from '@/components/date-picker/date-time-picker';
import { addMinutes } from '@/components/date-picker/utils';
import { FormCard } from '@/components/form/form-card';
import { FormField } from '@/components/form/form-field';
import { FormGrid } from '@/components/form/form-grid';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { PageHeader } from '@/components/page-header';
import type { ComboboxOption } from '@/components/searchable-combobox';
import { SearchableCombobox } from '@/components/searchable-combobox';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { LoadingButton } from '@/components/loading-button';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useDateFormat } from '@/hooks/use-date-format';
import { useLongBookingConfirm } from '@/hooks/use-long-booking-confirm';
import { toDateTimeLocal, toZonedIso } from '@/lib/datetime';
import { create, edit, index, show, store, update } from '@/routes/bookings';
import type { BookingFormProps, CarOption, EmployeeOption } from '@/types';

const AVAILABILITY_DEBOUNCE_MS = 400;

type ScheduleForm = {
    employee_id: number | null;
    car_id: number | null;
    start_at: string;
    planned_return_at: string;
    purpose: string;
};

/** Kunci rentang yang valid (kembali setelah mulai), atau '' bila belum lengkap. */
function rangeKey(start: string, end: string): string {
    // Format "Y-m-dTH:i" sama panjang, sehingga perbandingan string aman.
    return start !== '' && end !== '' && end > start ? `${start}|${end}` : '';
}

/** Hanya karyawan aktif yang dikirim server; yang nonaktif disembunyikan. */
function employeeOptions(employees: EmployeeOption[]): ComboboxOption[] {
    return employees.map((employee) => ({
        value: employee.id,
        label: employee.name,
        description: `${employee.division} · ${employee.phone}`,
        keywords: `${employee.division} ${employee.phone}`,
    }));
}

function carOptions(
    cars: CarOption[],
    selected: number | null,
): ComboboxOption[] {
    return cars.map((car) => ({
        value: car.id,
        label: `${car.brand} ${car.model}`,
        description: car.plate_number,
        keywords: `${car.plate_number} ${car.brand} ${car.model}`,
        hint:
            car.available === false ? (
                <span className="text-destructive">{car.reason}</span>
            ) : car.available ? (
                <span className="text-success">Tersedia</span>
            ) : undefined,
        // Mobil yang sedang dipilih tetap bisa dipilih ulang agar form edit
        // tidak terkunci; server tetap menolak bila bentrok.
        disabled: car.available === false && car.id !== selected,
    }));
}

export default function BookingFormPage({
    booking,
    employees,
    cars,
    startToleranceMinutes,
}: BookingFormProps) {
    const { timezone, bookingRules } = usePage().props;
    const { toDateTimeLocal: toLocal } = useDateFormat();
    const form = useForm<ScheduleForm>({
        employee_id: booking?.employee.id ?? null,
        car_id: booking?.car.id ?? null,
        start_at: booking ? toLocal(booking.start_at) : '',
        planned_return_at: booking ? toLocal(booking.planned_return_at) : '',
        purpose: booking?.purpose ?? '',
    });
    // Batas pilihan di kalender; aturan sebenarnya divalidasi server
    // (AGENTS.md 5.11).
    const earliestStart = addMinutes(
        toDateTimeLocal(new Date(), timezone),
        -startToleranceMinutes,
    );
    const [checkingCars, setCheckingCars] = useState(false);

    const range = rangeKey(form.data.start_at, form.data.planned_return_at);
    // Rentang yang ketersediaannya sudah dimuat server (saat edit: rentang
    // booking itu sendiri).
    const requestedRange = useRef(range);
    const formUrl = booking ? edit.url(booking.id) : create.url();

    useEffect(() => {
        if (range === '' || range === requestedRange.current) {
            return;
        }

        const [start, end] = range.split('|');
        const timeout = setTimeout(() => {
            requestedRange.current = range;
            router.get(
                formUrl,
                {
                    start_at: toZonedIso(start, timezone),
                    planned_return_at: toZonedIso(end, timezone),
                },
                {
                    only: ['cars'],
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    onStart: () => setCheckingCars(true),
                    onFinish: () => setCheckingCars(false),
                },
            );
        }, AVAILABILITY_DEBOUNCE_MS);

        return () => clearTimeout(timeout);
    }, [range, formUrl, timezone]);

    const selectedCar = cars.find((car) => car.id === form.data.car_id);

    const employeeListOptions = useMemo(
        () => employeeOptions(employees),
        [employees],
    );
    const carListOptions = useMemo(
        () => carOptions(cars, form.data.car_id),
        [cars, form.data.car_id],
    );

    const longBooking = useLongBookingConfirm();

    const save = () => {
        // Server menerima ISO 8601 dengan offset zona waktu aplikasi.
        form.transform((data) => ({
            ...data,
            start_at: toZonedIso(data.start_at, timezone),
            planned_return_at: toZonedIso(data.planned_return_at, timezone),
        }));
        form.submit(booking ? update(booking.id) : store(), {
            preserveScroll: true,
        });
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        longBooking.confirmIfLong(
            form.data.start_at,
            form.data.planned_return_at,
            save,
        );
    };

    return (
        <>
            <Head title={booking ? `Edit ${booking.code}` : 'Jadwalkan'} />

            <div className="flex min-w-0 flex-1 flex-col gap-4 p-4">
                <PageHeader
                    title={
                        booking
                            ? `Edit Jadwal ${booking.code}`
                            : 'Jadwalkan Peminjaman'
                    }
                    description={
                        booking
                            ? 'Ubah karyawan, mobil, waktu, atau tujuan selama mobil belum diserahterimakan kepada karyawan.'
                            : 'Catat permintaan peminjaman karyawan. Pilih waktu terlebih dahulu untuk melihat ketersediaan mobil.'
                    }
                />

                <form onSubmit={submit}>
                    <FormCard
                        title="Detail Peminjaman"
                        description="Isi data peminjaman mobil dinas. Kolom bertanda * wajib diisi."
                        footer={
                            <>
                                <Button
                                    variant="outline"
                                    asChild
                                    disabled={form.processing}
                                >
                                    <Link
                                        href={
                                            booking ? show(booking.id) : index()
                                        }
                                    >
                                        Batal
                                    </Link>
                                </Button>
                                <LoadingButton
                                    type="submit"
                                    loading={form.processing}
                                    data-test="booking-form-submit"
                                >
                                    {booking
                                        ? 'Simpan Perubahan'
                                        : 'Jadwalkan Peminjaman'}
                                </LoadingButton>
                            </>
                        }
                    >
                        <FormGrid>
                            <FormField
                                id="booking-employee"
                                label="Karyawan"
                                required
                                error={form.errors.employee_id}
                                span="full"
                            >
                                <SearchableCombobox
                                    id="booking-employee"
                                    options={employeeListOptions}
                                    value={form.data.employee_id}
                                    onChange={(id) =>
                                        form.setData('employee_id', id)
                                    }
                                    placeholder="Pilih karyawan"
                                    searchPlaceholder="Cari nama, no HP, atau divisi..."
                                    emptyText="Karyawan tidak ditemukan."
                                    invalid={!!form.errors.employee_id}
                                />
                            </FormField>

                            <FormField
                                id="booking-start"
                                label="Waktu mulai"
                                required
                                error={form.errors.start_at}
                                hint={`Paling awal ${startToleranceMinutes} menit sebelum sekarang, untuk karyawan yang datang langsung.`}
                            >
                                <DateTimePicker
                                    id="booking-start"
                                    value={form.data.start_at}
                                    onChange={(value) =>
                                        form.setData('start_at', value)
                                    }
                                    min={earliestStart}
                                    placeholder="Pilih waktu mulai"
                                    invalid={!!form.errors.start_at}
                                    aria-describedby="booking-start-hint"
                                />
                            </FormField>

                            <FormField
                                id="booking-return"
                                label="Rencana kembali"
                                required
                                error={form.errors.planned_return_at}
                            >
                                <DateTimePicker
                                    id="booking-return"
                                    value={form.data.planned_return_at}
                                    onChange={(value) =>
                                        form.setData('planned_return_at', value)
                                    }
                                    min={form.data.start_at || undefined}
                                    placeholder="Pilih rencana kembali"
                                    invalid={!!form.errors.planned_return_at}
                                />
                            </FormField>

                            <FormField
                                id="booking-car"
                                label="Mobil"
                                required
                                error={form.errors.car_id}
                                span="full"
                                labelAside={
                                    checkingCars && (
                                        <span className="flex items-center gap-1.5 text-xs text-muted-foreground">
                                            <Spinner
                                                className="size-3"
                                                aria-hidden="true"
                                            />
                                            Memeriksa ketersediaan...
                                        </span>
                                    )
                                }
                            >
                                <SearchableCombobox
                                    id="booking-car"
                                    options={carListOptions}
                                    value={form.data.car_id}
                                    onChange={(id) =>
                                        form.setData('car_id', id)
                                    }
                                    placeholder={
                                        range === ''
                                            ? 'Isi waktu mulai dan rencana kembali terlebih dahulu'
                                            : 'Pilih mobil'
                                    }
                                    searchPlaceholder="Cari merk, tipe, atau plat..."
                                    emptyText="Mobil tidak ditemukan."
                                    invalid={!!form.errors.car_id}
                                    disabled={range === ''}
                                    loading={checkingCars}
                                />
                                {selectedCar?.available === false &&
                                    !form.errors.car_id && (
                                        <Alert>
                                            <CircleAlert aria-hidden="true" />
                                            <AlertDescription>
                                                {selectedCar.brand}{' '}
                                                {selectedCar.model} (
                                                {selectedCar.plate_number})
                                                tidak tersedia pada waktu ini (
                                                {selectedCar.reason}). Pilih
                                                mobil lain atau ubah waktunya.
                                            </AlertDescription>
                                        </Alert>
                                    )}
                            </FormField>

                            <FormField
                                id="booking-purpose"
                                label="Tujuan / keperluan"
                                required
                                error={form.errors.purpose}
                                span="full"
                            >
                                <Textarea
                                    id="booking-purpose"
                                    value={form.data.purpose}
                                    onChange={(e) =>
                                        form.setData('purpose', e.target.value)
                                    }
                                    required
                                    minLength={bookingRules.purposeMinLength}
                                    maxLength={255}
                                    placeholder="Misalnya: kunjungan ke kantor cabang Bekasi"
                                    aria-invalid={
                                        !!form.errors.purpose || undefined
                                    }
                                />
                            </FormField>
                        </FormGrid>
                    </FormCard>
                </form>
            </div>

            <ConfirmDialog {...longBooking.dialog} />
        </>
    );
}

// Breadcrumb dihitung dari props saat render (bukan setLayoutProps di dalam
// komponen) agar hasil SSR sama dengan klien dan tidak terjadi hydration mismatch.
BookingFormPage.layout = ({ booking }: BookingFormProps) => ({
    breadcrumbs: booking
        ? [
              { title: 'Peminjaman', href: index() },
              { title: booking.code, href: show(booking.id) },
              { title: 'Edit Jadwal', href: edit(booking.id) },
          ]
        : [
              { title: 'Peminjaman', href: index() },
              { title: 'Jadwalkan', href: create() },
          ],
});
