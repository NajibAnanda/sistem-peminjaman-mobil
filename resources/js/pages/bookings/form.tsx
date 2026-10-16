import { Head, Link, router, useForm } from '@inertiajs/react';
import { CircleAlert } from 'lucide-react';
import type { FormEvent } from 'react';
import { useEffect, useRef, useState } from 'react';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import type { ComboboxOption } from '@/components/searchable-combobox';
import { SearchableCombobox } from '@/components/searchable-combobox';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useDateFormat } from '@/hooks/use-date-format';
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
    // Format datetime-local sama panjang, sehingga perbandingan string aman.
    return start !== '' && end !== '' && end > start ? `${start}|${end}` : '';
}

function employeeOptions(
    employees: EmployeeOption[],
    selected: number | null,
): ComboboxOption[] {
    return employees.map((employee) => ({
        value: employee.id,
        label: employee.name,
        description: `${employee.employee_number} · ${employee.division}`,
        keywords: `${employee.employee_number} ${employee.division}`,
        hint: employee.is_active ? undefined : (
            <span className="text-muted-foreground">Nonaktif</span>
        ),
        disabled: !employee.is_active && employee.id !== selected,
    }));
}

function carOptions(
    cars: CarOption[],
    selected: number | null,
): ComboboxOption[] {
    return cars.map((car) => ({
        value: car.id,
        label: car.plate_number,
        description: `${car.brand} ${car.model}`,
        keywords: `${car.brand} ${car.model}`,
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
    const { toDateTimeLocal } = useDateFormat();
    const form = useForm<ScheduleForm>({
        employee_id: booking?.employee.id ?? null,
        car_id: booking?.car.id ?? null,
        start_at: booking ? toDateTimeLocal(booking.start_at) : '',
        planned_return_at: booking
            ? toDateTimeLocal(booking.planned_return_at)
            : '',
        purpose: booking?.purpose ?? '',
    });
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
                { start_at: start, planned_return_at: end },
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
    }, [range, formUrl]);

    const selectedCar = cars.find((car) => car.id === form.data.car_id);

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.submit(booking ? update(booking.id) : store(), {
            preserveScroll: true,
        });
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
                            ? 'Jadwal masih dapat diubah selama mobil belum diserahterimakan.'
                            : 'Catat permintaan karyawan. Pilih waktu terlebih dahulu agar ketersediaan mobil terlihat.'
                    }
                />

                <Card className="max-w-3xl">
                    <CardContent>
                        <form onSubmit={submit} className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="booking-employee">
                                    Karyawan
                                </Label>
                                <SearchableCombobox
                                    id="booking-employee"
                                    options={employeeOptions(
                                        employees,
                                        form.data.employee_id,
                                    )}
                                    value={form.data.employee_id}
                                    onChange={(id) =>
                                        form.setData('employee_id', id)
                                    }
                                    placeholder="Pilih karyawan"
                                    searchPlaceholder="Cari nama, NIP, atau divisi..."
                                    emptyText="Karyawan tidak ditemukan."
                                    invalid={!!form.errors.employee_id}
                                />
                                <InputError message={form.errors.employee_id} />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid content-start gap-2">
                                    <Label htmlFor="booking-start">
                                        Waktu mulai
                                    </Label>
                                    <Input
                                        id="booking-start"
                                        type="datetime-local"
                                        value={form.data.start_at}
                                        onChange={(e) =>
                                            form.setData(
                                                'start_at',
                                                e.target.value,
                                            )
                                        }
                                        aria-invalid={
                                            !!form.errors.start_at || undefined
                                        }
                                        required
                                    />
                                    <p className="text-xs text-muted-foreground">
                                        Paling awal {startToleranceMinutes}{' '}
                                        menit sebelum sekarang, untuk karyawan
                                        yang datang langsung.
                                    </p>
                                    <InputError
                                        message={form.errors.start_at}
                                    />
                                </div>

                                <div className="grid content-start gap-2">
                                    <Label htmlFor="booking-return">
                                        Rencana kembali
                                    </Label>
                                    <Input
                                        id="booking-return"
                                        type="datetime-local"
                                        value={form.data.planned_return_at}
                                        min={form.data.start_at || undefined}
                                        onChange={(e) =>
                                            form.setData(
                                                'planned_return_at',
                                                e.target.value,
                                            )
                                        }
                                        aria-invalid={
                                            !!form.errors.planned_return_at ||
                                            undefined
                                        }
                                        required
                                    />
                                    <InputError
                                        message={form.errors.planned_return_at}
                                    />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center justify-between gap-2">
                                    <Label htmlFor="booking-car">Mobil</Label>
                                    {checkingCars && (
                                        <span className="flex items-center gap-1.5 text-xs text-muted-foreground">
                                            <Spinner className="size-3" />
                                            Memeriksa ketersediaan...
                                        </span>
                                    )}
                                </div>
                                <SearchableCombobox
                                    id="booking-car"
                                    options={carOptions(cars, form.data.car_id)}
                                    value={form.data.car_id}
                                    onChange={(id) =>
                                        form.setData('car_id', id)
                                    }
                                    placeholder={
                                        range === ''
                                            ? 'Isi waktu mulai dan rencana kembali terlebih dahulu'
                                            : 'Pilih mobil'
                                    }
                                    searchPlaceholder="Cari plat, merk, atau tipe..."
                                    emptyText="Mobil tidak ditemukan."
                                    invalid={!!form.errors.car_id}
                                    disabled={range === ''}
                                />
                                {selectedCar?.available === false &&
                                    !form.errors.car_id && (
                                        <Alert>
                                            <CircleAlert aria-hidden="true" />
                                            <AlertDescription>
                                                {selectedCar.plate_number} tidak
                                                tersedia pada waktu ini (
                                                {selectedCar.reason}). Pilih
                                                mobil lain atau ubah waktunya.
                                            </AlertDescription>
                                        </Alert>
                                    )}
                                <InputError message={form.errors.car_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="booking-purpose">
                                    Tujuan / keperluan
                                </Label>
                                <Textarea
                                    id="booking-purpose"
                                    value={form.data.purpose}
                                    onChange={(e) =>
                                        form.setData('purpose', e.target.value)
                                    }
                                    required
                                    maxLength={255}
                                    placeholder="Misalnya: kunjungan ke kantor cabang Bekasi"
                                    aria-invalid={
                                        !!form.errors.purpose || undefined
                                    }
                                />
                                <InputError message={form.errors.purpose} />
                            </div>

                            <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
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
                                <Button
                                    type="submit"
                                    disabled={form.processing}
                                    data-test="booking-form-submit"
                                >
                                    {form.processing && <Spinner />}
                                    {booking
                                        ? 'Simpan Perubahan'
                                        : 'Jadwalkan Peminjaman'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
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
