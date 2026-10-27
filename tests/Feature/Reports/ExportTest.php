<?php

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingExtension;
use App\Models\Car;
use App\Models\Employee;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

const EXPORT_HEADINGS = [
    'Kode Peminjaman', 'Nama Pegawai', 'Divisi', 'No HP', 'Mobil', 'Plat Nomor', 'Tujuan',
    'Waktu Mulai', 'Rencana Kembali', 'Waktu Ambil', 'Waktu Kembali', 'Status',
    'Durasi Keterlambatan',
];

// Ekspor wajib berperiode (config fleet.export.max_range_days).
const EXPORT_PERIOD = ['from' => '2026-09-01', 'to' => '2026-10-31'];

beforeEach(function () {
    /** @var TestCase $this */
    $this->travelTo(CarbonImmutable::parse('2026-10-20 12:00:00'));
    $this->user = User::factory()->admin()->create(['name' => 'Sari Petugas']);
    $creator = User::factory()->admin()->create(['name' => 'Dewi Pencatat']);

    $car = Car::factory()->create(['plate_number' => 'B 1234 ABC', 'brand' => 'Toyota', 'model' => 'Avanza']);
    // No HP berupa angka dengan nol di depan harus tetap utuh sebagai teks di file.
    $employee = Employee::factory()->create(['name' => 'Ayu Ningsih Pratiwi', 'division' => 'Keuangan', 'phone' => '081234567890']);

    $this->late = Booking::factory()->create([
        'code' => 'PMJ-202609-9001',
        'car_id' => $car->id,
        'employee_id' => $employee->id,
        'created_by' => $creator->id,
        'purpose' => 'Kunjungan ke Kantor Cabang Bekasi — “urgent”',
        'status' => BookingStatus::Completed,
        'start_at' => CarbonImmutable::parse('2026-09-10 08:00'),
        'planned_return_at' => CarbonImmutable::parse('2026-09-10 12:00'),
        'picked_up_at' => CarbonImmutable::parse('2026-09-10 07:45'),
        'returned_at' => CarbonImmutable::parse('2026-09-11 15:30'),
    ]);
    BookingExtension::factory()->count(2)->for($this->late)->create();

    $this->scheduled = Booking::factory()->create([
        'code' => 'PMJ-202610-9001',
        'car_id' => $car->id,
        'employee_id' => $employee->id,
        'created_by' => $creator->id,
        'status' => BookingStatus::Scheduled,
        'start_at' => CarbonImmutable::parse('2026-10-22 08:00'),
        'planned_return_at' => CarbonImmutable::parse('2026-10-22 12:00'),
    ]);
});

function exportedFile(TestResponse $response): string
{
    /** @var BinaryFileResponse $fileResponse */
    $fileResponse = $response->baseResponse;

    return $fileResponse->getFile()->getPathname();
}

/**
 * @return list<list<string>>
 */
function csvRows(TestResponse $response, string $delimiter = ','): array
{
    $content = (string) file_get_contents(exportedFile($response));
    expect(substr($content, 0, 3))->toBe("\xEF\xBB\xBF");

    $handle = fopen('php://memory', 'r+');
    fwrite($handle, substr($content, 3));
    rewind($handle);

    $rows = [];
    while (($row = fgetcsv($handle, null, $delimiter, '"', '')) !== false) {
        $rows[] = $row;
    }

    return $rows;
}

test('CSV berisi kolom laporan lengkap dengan tanggal dan teks Indonesia', function () {
    $response = $this->actingAs($this->user)
        ->get(route('history.export', [...EXPORT_PERIOD, 'format' => 'csv', 'status' => ['completed']]))
        ->assertOk()
        ->assertDownload('riwayat-peminjaman_2026-09-01_sd_2026-10-31_diekspor-2026-10-20-1200.csv');

    expect(csvRows($response))->toBe([
        EXPORT_HEADINGS,
        [
            'PMJ-202609-9001', 'Ayu Ningsih Pratiwi', 'Keuangan', '081234567890', 'Toyota Avanza', 'B 1234 ABC',
            'Kunjungan ke Kantor Cabang Bekasi — “urgent”',
            '10 Sep 2026, 08.00', '10 Sep 2026, 12.00', '10 Sep 2026, 07.45', '11 Sep 2026, 15.30',
            'Selesai', '1 hari 3 jam',
        ],
    ]);
});

test('peminjaman dibatalkan menyertakan alasan pembatalan di kolom tujuan', function () {
    Booking::factory()->create([
        'code' => 'PMJ-202609-9002',
        'car_id' => $this->scheduled->car_id,
        'employee_id' => $this->scheduled->employee_id,
        'purpose' => 'Rapat koordinasi cabang',
        'cancellation_reason' => 'Rapat dibatalkan oleh pihak klien',
        'status' => BookingStatus::Cancelled,
        'start_at' => CarbonImmutable::parse('2026-09-15 08:00'),
        'planned_return_at' => CarbonImmutable::parse('2026-09-15 12:00'),
    ]);

    $rows = csvRows($this->actingAs($this->user)->get(route('history.export', [...EXPORT_PERIOD, 'format' => 'csv', 'status' => ['cancelled']])));

    expect($rows[1][6])->toBe('Rapat koordinasi cabang (Dibatalkan: Rapat dibatalkan oleh pihak klien)');
});

test('ekspor mengikuti filter dan periode yang sama dengan tabel', function () {
    $this->actingAs($this->user);

    $all = csvRows($this->get(route('history.export', [...EXPORT_PERIOD, 'format' => 'csv'])));
    $scheduledOnly = csvRows($this->get(route('history.export', ['format' => 'csv', 'from' => '2026-10-01', 'to' => '2026-10-31'])));
    $none = csvRows($this->get(route('history.export', [...EXPORT_PERIOD, 'format' => 'csv', 'search' => 'tidak-ada'])));

    // Urutan sama dengan tabel Riwayat: terbaru di atas.
    expect(array_column(array_slice($all, 1), 0))->toBe(['PMJ-202610-9001', 'PMJ-202609-9001'])
        ->and(array_column(array_slice($scheduledOnly, 1), 0))->toBe(['PMJ-202610-9001'])
        ->and($scheduledOnly[1][9])->toBe('')
        ->and($scheduledOnly[1][11])->toBe('Dijadwalkan')
        ->and($scheduledOnly[1][12])->toBe('')
        ->and($none)->toBe([EXPORT_HEADINGS]);

    $this->get(route('history.export', ['format' => 'csv', 'from' => '2026-10-01', 'to' => '2026-10-31']))
        ->assertDownload('riwayat-peminjaman_2026-10-01_sd_2026-10-31_diekspor-2026-10-20-1200.csv');
});

test('pemisah CSV mengikuti config', function () {
    config(['fleet.export.csv_delimiter' => ';']);

    $rows = csvRows($this->actingAs($this->user)->get(route('history.export', [...EXPORT_PERIOD, 'format' => 'csv', 'status' => ['scheduled']])), ';');

    expect($rows[0])->toBe(EXPORT_HEADINGS)
        ->and($rows[1][0])->toBe('PMJ-202610-9001');
});

test('file Excel berisi judul tebal dan no HP tersimpan sebagai teks', function () {
    $response = $this->actingAs($this->user)
        ->get(route('history.export', [...EXPORT_PERIOD, 'format' => 'xlsx', 'status' => ['completed']]))
        ->assertOk()
        ->assertDownload('riwayat-peminjaman_2026-09-01_sd_2026-10-31_diekspor-2026-10-20-1200.xlsx');

    $sheet = IOFactory::load(exportedFile($response))->getActiveSheet();

    expect($sheet->rangeToArray('A1:M1')[0])->toBe(EXPORT_HEADINGS)
        ->and($sheet->getStyle('A1')->getFont()->getBold())->toBeTrue()
        ->and($sheet->getHighestRow())->toBe(2)
        ->and($sheet->getCell('D2')->getValue())->toBe('081234567890')
        ->and($sheet->getCell('D2')->getDataType())->toBe(DataType::TYPE_STRING)
        ->and($sheet->getCell('H2')->getValue())->toBe('10 Sep 2026, 08.00')
        ->and($sheet->getCell('M2')->getValue())->toBe('1 hari 3 jam')
        ->and(IOFactory::load(exportedFile($this->get(route('history.export', [...EXPORT_PERIOD, 'format' => 'xlsx', 'status' => ['scheduled']]))))
            ->getActiveSheet()->getCell('M2')->getValue())->toBe('');
});

test('ekspor besar dibaca bertahap tanpa baris hilang atau ganda', function () {
    config(['fleet.export.chunk_size' => 3]);
    Booking::factory()->count(7)->historical(now()->subDays(10))->create();

    $rows = csvRows($this->actingAs($this->user)->get(route('history.export', [...EXPORT_PERIOD, 'format' => 'csv'])));
    $codes = array_column(array_slice($rows, 1), 0);

    expect($codes)->toHaveCount(9)
        ->and(array_unique($codes))->toHaveCount(9);
});

test('ekspor tercatat di log aktivitas', function () {
    $this->actingAs($this->user)->get(route('history.export', [...EXPORT_PERIOD, 'format' => 'xlsx', 'status' => ['completed']]));

    $activity = lastActivity();

    expect($activity->log_name)->toBe(ActivityLogName::Report->value)
        ->and($activity->event)->toBe(ActivityEvent::Exported->value)
        ->and($activity->causer_id)->toBe($this->user->id)
        ->and($activity->properties['format'])->toBe('xlsx')
        ->and($activity->properties['rows'])->toBe(1)
        ->and($activity->properties['query']['filters']['status'])->toBe(['completed']);
});

test('ekspor PDF menghasilkan file download dengan nama dan header yang tepat', function () {
    $response = $this->actingAs($this->user)
        ->get(route('history.export', [...EXPORT_PERIOD, 'format' => 'pdf', 'status' => ['completed']]))
        ->assertOk()
        ->assertDownload('riwayat-peminjaman_2026-09-01_sd_2026-10-31_diekspor-2026-10-20-1200.pdf');

    expect($response->headers->get('content-type'))->toBe('application/pdf');
});

test('format tidak dikenal ditolak', function (?string $format) {
    $this->actingAs($this->user)
        ->get(route('history.export', [...EXPORT_PERIOD, ...array_filter(['format' => $format])]))
        ->assertSessionHasErrors('format');
})->with(['docx', null]);

test('tamu dan akun nonaktif tidak dapat mengekspor', function () {
    $this->get(route('history.export', [...EXPORT_PERIOD, 'format' => 'csv']))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->inactive()->create())
        ->get(route('history.export', [...EXPORT_PERIOD, 'format' => 'csv']))
        ->assertRedirect(route('login'));
});

test('Pengelola juga dapat mengekspor', function () {
    $this->actingAs(User::factory()->manager()->create())
        ->get(route('history.export', [...EXPORT_PERIOD, 'format' => 'csv']))
        ->assertOk();
});

test('ekspor tanpa periode ditolak', function () {
    $this->actingAs($this->user)
        ->get(route('history.export', ['format' => 'csv']))
        ->assertSessionHasErrors(['period' => 'Pilih periode terlebih dahulu (maksimal 366 hari) sebelum mengekspor.']);

    $this->get(route('history.export', ['format' => 'csv', 'from' => '2026-10-01']))
        ->assertSessionHasErrors('period');
});

test('rentang periode ekspor dibatasi config', function () {
    $this->actingAs($this->user);

    // 1 Nov 2025 – 1 Nov 2026 = 366 hari (inklusif): masih boleh.
    $this->get(route('history.export', ['format' => 'csv', 'from' => '2025-11-01', 'to' => '2026-11-01']))
        ->assertOk();

    $this->get(route('history.export', ['format' => 'csv', 'from' => '2025-10-31', 'to' => '2026-11-01']))
        ->assertSessionHasErrors(['period' => 'Rentang periode ekspor maksimal 366 hari, sedangkan periode yang dipilih 367 hari. Persempit periodenya.']);

    config(['fleet.export.max_range_days' => 31]);

    $this->get(route('history.export', ['format' => 'csv', 'from' => '2026-09-01', 'to' => '2026-10-31']))
        ->assertSessionHasErrors('period');
});

test('permintaan ekspor JSON yang ditolak mengembalikan pesan untuk ditampilkan', function () {
    $this->actingAs($this->user)
        ->getJson(route('history.export', ['format' => 'csv']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['period']);
});
