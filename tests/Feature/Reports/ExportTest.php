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

const EXPORT_HEADINGS = [
    'Kode Peminjaman', 'Nama Karyawan', 'NIP', 'Divisi', 'Mobil', 'Plat Nomor', 'Tujuan',
    'Waktu Mulai', 'Rencana Kembali', 'Waktu Ambil', 'Waktu Kembali', 'Status',
    'Jumlah Perpanjangan', 'Durasi Keterlambatan', 'Petugas Pencatat',
];

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-20 12:00:00'));
    $this->user = User::factory()->admin()->create(['name' => 'Sari Petugas']);
    $creator = User::factory()->admin()->create(['name' => 'Dewi Pencatat']);

    $car = Car::factory()->create(['plate_number' => 'B 1234 ABC', 'brand' => 'Toyota', 'model' => 'Avanza']);
    // NIP berupa angka dengan nol di depan harus tetap utuh di file.
    $employee = Employee::factory()->create(['name' => 'Ayu Ningsih Pratiwi', 'employee_number' => '00123', 'division' => 'Keuangan']);

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
    return $response->baseResponse->getFile()->getPathname();
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
        ->get(route('history.export', ['format' => 'csv', 'status' => ['completed']]))
        ->assertOk()
        ->assertDownload('riwayat-peminjaman_semua-periode_diekspor-2026-10-20-1200.csv');

    expect(csvRows($response))->toBe([
        EXPORT_HEADINGS,
        [
            'PMJ-202609-9001', 'Ayu Ningsih Pratiwi', '00123', 'Keuangan', 'Toyota Avanza', 'B 1234 ABC',
            'Kunjungan ke Kantor Cabang Bekasi — “urgent”',
            '10 Sep 2026, 08.00', '10 Sep 2026, 12.00', '10 Sep 2026, 07.45', '11 Sep 2026, 15.30',
            'Selesai', '2', '1 hari 3 jam', 'Dewi Pencatat',
        ],
    ]);
});

test('ekspor mengikuti filter dan periode yang sama dengan tabel', function () {
    $this->actingAs($this->user);

    $all = csvRows($this->get(route('history.export', ['format' => 'csv'])));
    $scheduledOnly = csvRows($this->get(route('history.export', ['format' => 'csv', 'from' => '2026-10-01', 'to' => '2026-10-31'])));
    $none = csvRows($this->get(route('history.export', ['format' => 'csv', 'search' => 'tidak-ada'])));

    // Urutan sama dengan tabel Riwayat: terbaru di atas.
    expect(array_column(array_slice($all, 1), 0))->toBe(['PMJ-202610-9001', 'PMJ-202609-9001'])
        ->and(array_column(array_slice($scheduledOnly, 1), 0))->toBe(['PMJ-202610-9001'])
        ->and($scheduledOnly[1][9])->toBe('')
        ->and($scheduledOnly[1][11])->toBe('Dijadwalkan')
        // Tanpa perpanjangan ditulis 0, bukan sel kosong.
        ->and($scheduledOnly[1][12])->toBe('0')
        ->and($scheduledOnly[1][13])->toBe('')
        ->and($none)->toBe([EXPORT_HEADINGS]);

    $this->get(route('history.export', ['format' => 'csv', 'from' => '2026-10-01', 'to' => '2026-10-31']))
        ->assertDownload('riwayat-peminjaman_2026-10-01_sd_2026-10-31_diekspor-2026-10-20-1200.csv');
});

test('pemisah CSV mengikuti config', function () {
    config(['fleet.export.csv_delimiter' => ';']);

    $rows = csvRows($this->actingAs($this->user)->get(route('history.export', ['format' => 'csv', 'status' => ['scheduled']])), ';');

    expect($rows[0])->toBe(EXPORT_HEADINGS)
        ->and($rows[1][0])->toBe('PMJ-202610-9001');
});

test('file Excel berisi judul tebal dan NIP tersimpan sebagai teks', function () {
    $response = $this->actingAs($this->user)
        ->get(route('history.export', ['format' => 'xlsx', 'status' => ['completed']]))
        ->assertOk()
        ->assertDownload('riwayat-peminjaman_semua-periode_diekspor-2026-10-20-1200.xlsx');

    $sheet = IOFactory::load(exportedFile($response))->getActiveSheet();

    expect($sheet->rangeToArray('A1:O1')[0])->toBe(EXPORT_HEADINGS)
        ->and($sheet->getStyle('A1')->getFont()->getBold())->toBeTrue()
        ->and($sheet->getHighestRow())->toBe(2)
        ->and($sheet->getCell('C2')->getValue())->toBe('00123')
        ->and($sheet->getCell('C2')->getDataType())->toBe(DataType::TYPE_STRING)
        ->and($sheet->getCell('H2')->getValue())->toBe('10 Sep 2026, 08.00')
        ->and($sheet->getCell('M2')->getValue())->toBe(2)
        ->and(IOFactory::load(exportedFile($this->get(route('history.export', ['format' => 'xlsx', 'status' => ['scheduled']]))))
            ->getActiveSheet()->getCell('M2')->getValue())->toBe(0)
        ->and($sheet->getCell('N2')->getValue())->toBe('1 hari 3 jam');
});

test('ekspor besar dibaca bertahap tanpa baris hilang atau ganda', function () {
    config(['fleet.export.chunk_size' => 3]);
    Booking::factory()->count(7)->historical()->create();

    $rows = csvRows($this->actingAs($this->user)->get(route('history.export', ['format' => 'csv'])));
    $codes = array_column(array_slice($rows, 1), 0);

    expect($codes)->toHaveCount(9)
        ->and(array_unique($codes))->toHaveCount(9);
});

test('ekspor tercatat di log aktivitas', function () {
    $this->actingAs($this->user)->get(route('history.export', ['format' => 'xlsx', 'status' => ['completed']]));

    $activity = lastActivity();

    expect($activity->log_name)->toBe(ActivityLogName::Report->value)
        ->and($activity->event)->toBe(ActivityEvent::Exported->value)
        ->and($activity->causer_id)->toBe($this->user->id)
        ->and($activity->properties['format'])->toBe('xlsx')
        ->and($activity->properties['rows'])->toBe(1)
        ->and($activity->properties['query']['filters']['status'])->toBe(['completed']);
});

test('format tidak dikenal ditolak', function (?string $format) {
    $this->actingAs($this->user)
        ->get(route('history.export', array_filter(['format' => $format])))
        ->assertSessionHasErrors('format');
})->with(['pdf', null]);

test('tamu dan akun nonaktif tidak dapat mengekspor', function () {
    $this->get(route('history.export', ['format' => 'csv']))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->inactive()->create())
        ->get(route('history.export', ['format' => 'csv']))
        ->assertRedirect(route('login'));
});

test('Pengelola juga dapat mengekspor', function () {
    $this->actingAs(User::factory()->manager()->create())
        ->get(route('history.export', ['format' => 'csv']))
        ->assertOk();
});
