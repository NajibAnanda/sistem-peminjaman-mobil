<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BookingDisplayStatus;
use App\Enums\BookingStatus;
use App\Enums\LatenessFilter;
use App\Support\TableQuery;
use Carbon\CarbonInterface;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property int $employee_id
 * @property int $car_id
 * @property string $purpose
 * @property Carbon $start_at
 * @property Carbon $planned_return_at
 * @property Carbon|null $picked_up_at
 * @property Carbon|null $returned_at
 * @property BookingStatus $status
 * @property string|null $return_notes
 * @property string|null $cancellation_reason
 * @property Carbon|null $cancelled_at
 * @property int $created_by
 * @property Carbon $created_at
 */
#[Fillable([
    'code', 'employee_id', 'car_id', 'purpose', 'start_at', 'planned_return_at',
    'picked_up_at', 'returned_at', 'status', 'return_notes', 'cancellation_reason',
    'cancelled_at', 'created_by', 'picked_up_by', 'returned_by', 'cancelled_by',
])]
final class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'start_at' => 'datetime',
            'planned_return_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'returned_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<Car, $this>
     */
    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pickedUpBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'picked_up_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * @return HasMany<BookingPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(BookingPhoto::class);
    }

    /**
     * @return HasMany<BookingExtension, $this>
     */
    public function extensions(): HasMany
    {
        return $this->hasMany(BookingExtension::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * Peminjaman yang masih berjalan (Dijadwalkan atau Aktif).
     *
     * @param  Builder<self>  $query
     */
    public function scopeOngoing(Builder $query): void
    {
        $query->whereIn('status', BookingStatus::ongoing());
    }

    /**
     * Peminjaman berjalan yang rentangnya tumpang tindih dengan [$start, $end)
     * (AGENTS.md 5.3): baru.start < lama.akhir DAN baru.akhir > lama.start.
     * Untuk peminjaman Aktif yang terlambat, lama.akhir = sekarang karena mobil
     * dianggap terpakai sampai benar-benar dikembalikan. Batas yang tepat
     * bersentuhan tidak dianggap bentrok.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOverlapping(Builder $query, CarbonInterface $start, CarbonInterface $end, ?CarbonInterface $now = null): void
    {
        $now ??= now();

        $query->ongoing()
            ->where('start_at', '<', $end)
            ->where(function (Builder $query) use ($start, $now): void {
                $query->where('planned_return_at', '>', $start);

                // Akhir peminjaman terlambat = sekarang, jadi bentrok hanya bila
                // jadwal baru dimulai sebelum sekarang.
                if ($now->greaterThan($start)) {
                    $query->orWhere(fn (Builder $query) => $query
                        ->where('status', BookingStatus::Active)
                        ->where('planned_return_at', '<', $now));
                }
            });
    }

    /**
     * Terlambat: Aktif dan rencana kembali sudah lewat (AGENTS.md 5.2).
     *
     * @param  Builder<self>  $query
     */
    public function scopeOverdue(Builder $query, ?CarbonInterface $now = null): void
    {
        $query->where('status', BookingStatus::Active)->where('planned_return_at', '<', $now ?? now());
    }

    /**
     * Belum Diambil: Dijadwalkan dan waktu mulai sudah lewat (AGENTS.md 5.2).
     *
     * @param  Builder<self>  $query
     */
    public function scopeNotPickedUp(Builder $query, ?CarbonInterface $now = null): void
    {
        $query->where('status', BookingStatus::Scheduled)->where('start_at', '<', $now ?? now());
    }

    /**
     * FR-23: menambahkan kolom `awaiting_return_code`, yaitu kode peminjaman
     * Terlambat lain yang masih memakai mobil yang sama. Dihitung dalam satu
     * query (subquery) agar daftar tidak N+1.
     *
     * @param  Builder<self>  $query
     */
    public function scopeWithAwaitingReturn(Builder $query, ?CarbonInterface $now = null): void
    {
        $query->addSelect([
            'awaiting_return_code' => self::query()
                ->from('bookings', 'previous')
                ->select('previous.code')
                ->whereColumn('previous.car_id', 'bookings.car_id')
                ->whereColumn('previous.id', '!=', 'bookings.id')
                ->where('previous.status', BookingStatus::Active)
                ->where('previous.planned_return_at', '<', $now ?? now())
                ->orderBy('previous.planned_return_at')
                ->limit(1),
        ]);
    }

    /**
     * Kode peminjaman Terlambat yang menahan mobil untuk jadwal ini (FR-23).
     * Hanya terisi bila query memakai scope withAwaitingReturn() dan booking
     * masih Dijadwalkan.
     */
    public function awaitingReturnCode(): ?string
    {
        $code = $this->getAttributes()['awaiting_return_code'] ?? null;

        return $this->status === BookingStatus::Scheduled && is_string($code) ? $code : null;
    }

    /**
     * Kandidat peminjaman yang tampil di Jadwal Armada pada [$start, $end).
     * Dibatalkan tidak ditampilkan. Penyaringan tepat dilakukan dengan
     * timelineRange() karena waktu aktual (ambil/kembali) bisa berbeda dari
     * rencana.
     *
     * @param  Builder<self>  $query
     */
    public function scopeVisibleOnTimeline(Builder $query, CarbonInterface $start, CarbonInterface $end): void
    {
        // Dua bagian digabung UNION agar masing-masing memakai index-nya
        // sendiri; satu query dengan OR lintas kolom memindai seluruh riwayat.
        // Peminjaman berjalan jumlahnya sedikit, termasuk yang Terlambat
        // (tetap memakai mobil sampai dikembalikan).
        $query->ongoing()
            ->where(fn (Builder $query) => $query
                ->where('start_at', '<', $end)
                ->orWhere('picked_up_at', '<', $end))
            ->union(self::query()
                ->where('status', BookingStatus::Completed)
                ->where('returned_at', '>', $start)
                ->where('picked_up_at', '<', $end));
    }

    /**
     * Rentang pemakaian mobil di timeline: waktu aktual bila sudah ada, dan
     * peminjaman Terlambat dianggap berlangsung sampai sekarang.
     *
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    public function timelineRange(CarbonInterface $now): array
    {
        $start = $this->picked_up_at ?? $this->start_at;
        $end = $this->returned_at
            ?? ($this->status === BookingStatus::Active && $this->planned_return_at->lessThan($now) ? $now : $this->planned_return_at);

        return [$start, $end];
    }

    /**
     * Selesai terlambat: dikembalikan setelah rencana kembali (AGENTS.md 5.2).
     *
     * @param  Builder<self>  $query
     */
    public function scopeCompletedLate(Builder $query): void
    {
        $query->where('status', BookingStatus::Completed)->whereColumn('returned_at', '>', 'planned_return_at');
    }

    /**
     * Filter berdasarkan status tampilan (termasuk indikator turunan).
     *
     * @param  Builder<self>  $query
     * @param  list<string>  $statuses  nilai BookingDisplayStatus
     */
    public function scopeWhereDisplayStatus(Builder $query, array $statuses): void
    {
        $now = now();

        $query->where(function (Builder $query) use ($statuses, $now): void {
            foreach ($statuses as $status) {
                $query->orWhere(fn (Builder $query) => match (BookingDisplayStatus::from($status)) {
                    BookingDisplayStatus::Scheduled => $query->where('status', BookingStatus::Scheduled)->where('start_at', '>=', $now),
                    BookingDisplayStatus::NotPickedUp => $query->notPickedUp($now),
                    BookingDisplayStatus::Active => $query->where('status', BookingStatus::Active)->where('planned_return_at', '>=', $now),
                    BookingDisplayStatus::Overdue => $query->overdue($now),
                    BookingDisplayStatus::Completed => $query->where('status', BookingStatus::Completed),
                    BookingDisplayStatus::Cancelled => $query->where('status', BookingStatus::Cancelled),
                });
            }
        });
    }

    /**
     * Pencarian dan filter tabel Peminjaman (pola tabel standar).
     *
     * @param  Builder<self>  $query
     */
    public function scopeForTable(Builder $query, TableQuery $table): void
    {
        $query
            ->when($table->search, fn (Builder $query, string $term) => $query->search($term))
            ->when($table->filter('status'), fn (Builder $query, array $statuses) => $query->whereDisplayStatus($statuses));
    }

    /**
     * Pencarian kode, nama/NIP karyawan, atau plat nomor.
     *
     * @param  Builder<self>  $query
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        // Subquery IN (dihitung sekali), bukan whereHas/EXISTS yang dijalankan
        // ulang untuk setiap baris peminjaman: jauh lebih cepat pada riwayat besar.
        $query->where(fn (Builder $query) => $query
            ->where('code', 'like', "%{$term}%")
            ->orWhereIn('employee_id', Employee::query()->select('id')->where(fn (Builder $query) => $query
                ->where('name', 'like', "%{$term}%")
                ->orWhere('employee_number', 'like', "%{$term}%")))
            ->orWhereIn('car_id', Car::query()->select('id')->where('plate_number', 'like', "%{$term}%")));
    }

    /**
     * Pencarian dan filter Riwayat (FR-25). Dipakai bersama oleh tabel dan
     * ekspor (FR-26) agar isi file selalu sama dengan data yang tampil.
     * Periode dihitung dari waktu mulai.
     *
     * @param  Builder<self>  $query
     */
    public function scopeForHistory(Builder $query, TableQuery $table, ?CarbonInterface $now = null): void
    {
        $now ??= now();

        $query
            ->when($table->search, fn (Builder $query, string $term) => $query->search($term))
            ->when($table->filter('status'), fn (Builder $query, array $statuses) => $query->whereDisplayStatus($statuses))
            ->when($table->filter('car'), fn (Builder $query, array $ids) => $query->whereIn('car_id', $ids))
            ->when($table->filter('employee'), fn (Builder $query, array $ids) => $query->whereIn('employee_id', $ids))
            ->when($table->filter('division'), fn (Builder $query, array $divisions) => $query
                ->whereIn('employee_id', Employee::query()->select('id')->whereIn('division', $divisions)))
            ->when($table->filter('lateness'), fn (Builder $query, array $values) => $query->where(
                function (Builder $query) use ($values, $now): void {
                    foreach ($values as $value) {
                        $query->orWhere(fn (Builder $query) => match (LatenessFilter::from($value)) {
                            LatenessFilter::Late => $query->where(fn (Builder $query) => $query
                                ->completedLate()
                                ->orWhere(fn (Builder $query) => $query->overdue($now))),
                            LatenessFilter::OnTime => $query->where('status', BookingStatus::Completed)
                                ->whereColumn('returned_at', '<=', 'planned_return_at'),
                        });
                    }
                },
            ))
            ->when($table->from, fn (Builder $query, CarbonInterface $from) => $query->where('start_at', '>=', $from))
            ->when($table->to, fn (Builder $query, CarbonInterface $to) => $query->where('start_at', '<=', $to));
    }

    /**
     * Lama keterlambatan dalam menit: Selesai setelah rencana kembali, atau
     * masih Terlambat sampai sekarang. Null bila tidak terlambat.
     */
    public function latenessMinutes(?CarbonInterface $now = null): ?int
    {
        $end = match ($this->status) {
            BookingStatus::Completed => $this->returned_at,
            BookingStatus::Active => $now ?? now(),
            default => null,
        };

        if ($end === null || ! $end->greaterThan($this->planned_return_at)) {
            return null;
        }

        return (int) $this->planned_return_at->diffInMinutes($end);
    }

    public function displayStatus(?CarbonInterface $now = null): BookingDisplayStatus
    {
        return BookingDisplayStatus::resolve($this->status, $this->start_at, $this->planned_return_at, $now ?? now());
    }
}
