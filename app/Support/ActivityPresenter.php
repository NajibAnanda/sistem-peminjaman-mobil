<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\ActivityEvent;
use App\Enums\ActivityLogName;
use App\Enums\BookingStatus;
use App\Enums\CarOperationalStatus;
use App\Enums\ExportFormat;
use App\Enums\PhotoSource;
use App\Enums\StaffRole;
use App\Models\Booking;
use App\Models\Car;
use App\Models\Employee;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;
use Spatie\Activitylog\Models\Activity;
use Throwable;

/**
 * Menyajikan log aktivitas dalam bahasa yang mudah dipahami (FR-27): label
 * field Bahasa Indonesia, nama pegawai/plat alih-alih id, tanggal format
 * Indonesia, dan label status alih-alih nilai enum.
 */
final readonly class ActivityPresenter
{
    private const DATE_FIELDS = ['start_at', 'planned_return_at', 'picked_up_at', 'returned_at', 'cancelled_at'];

    /** Properti tambahan yang ditampilkan beserta labelnya; selebihnya disembunyikan. */
    private const DETAIL_LABELS = [
        'reason' => 'Alasan',
        'photo_source' => 'Sumber foto',
        'position' => 'Foto ke',
        'replaced' => 'Mengganti foto lama',
        'late' => 'Kembali terlambat',
        'via' => 'Dilakukan lewat',
        'format' => 'Format file',
        'rows' => 'Jumlah baris',
        'file' => 'Nama file',
    ];

    /**
     * @param  array<int, string>  $employees  id => nama
     * @param  array<int, string>  $cars  id => label mobil
     */
    private function __construct(
        private array $employees,
        private array $cars,
    ) {}

    /**
     * Memuat nama pegawai & plat yang dirujuk log di satu halaman sekaligus
     * (dua query, tanpa N+1).
     *
     * @param  iterable<Activity>  $activities
     */
    public static function for(iterable $activities): self
    {
        $ids = ['employee_id' => [], 'car_id' => []];

        foreach ($activities as $activity) {
            foreach (['old', 'attributes'] as $side) {
                $values = self::changeSet($activity, $side);

                foreach (array_keys($ids) as $key) {
                    if (isset($values[$key]) && is_numeric($values[$key])) {
                        $ids[$key][] = (int) $values[$key];
                    }
                }
            }
        }

        $cars = Car::query()
            ->whereKey(array_unique($ids['car_id']))
            ->get(['id', 'brand', 'model', 'plate_number'])
            ->mapWithKeys(fn (Car $car): array => [$car->id => "{$car->brand} {$car->model} ({$car->plate_number})"])
            ->all();

        return new self(
            Employee::query()->whereKey(array_unique($ids['employee_id']))->pluck('name', 'id')->all(),
            $cars,
        );
    }

    /**
     * @return array{id: int, created_at: string, causer_name: string, type: string|null, type_label: string, event_label: string|null, description: string, subject_label: string|null, changes: list<array{field: string, label: string, old: string|null, new: string|null}>, details: list<array{label: string, value: string}>}
     */
    public function present(Activity $activity): array
    {
        $type = ActivityLogName::tryFrom((string) $activity->log_name);
        $event = ActivityEvent::tryFrom((string) $activity->event);

        return [
            'id' => (int) $activity->id,
            'created_at' => $activity->created_at?->toIso8601String() ?? '',
            // Tanpa pelaku = dijalankan lewat perintah server (artisan).
            'causer_name' => $activity->causer instanceof User ? $activity->causer->name : 'Sistem',
            'type' => $type?->value,
            'type_label' => $type?->label() ?? 'Lainnya',
            'event_label' => $event?->label(),
            'description' => (string) $activity->description,
            'subject_label' => $this->subjectLabel($activity),
            'changes' => $this->changes($activity),
            'details' => $this->details($activity),
        ];
    }

    /**
     * @return list<array{field: string, label: string, old: string|null, new: string|null}>
     */
    private function changes(Activity $activity): array
    {
        $old = self::changeSet($activity, 'old');
        $new = self::changeSet($activity, 'attributes');
        $fields = array_values(array_unique([...array_keys($new), ...array_keys($old)]));

        return array_map(fn (string $field): array => [
            'field' => $field,
            'label' => $this->fieldLabel($field),
            'old' => array_key_exists($field, $old) ? $this->value($field, $old[$field]) : null,
            'new' => array_key_exists($field, $new) ? $this->value($field, $new[$field]) : null,
        ], $fields);
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    private function details(Activity $activity): array
    {
        $properties = $activity->properties?->all() ?? [];
        $details = [];

        foreach (self::DETAIL_LABELS as $key => $label) {
            if (! array_key_exists($key, $properties) || $properties[$key] === null || $properties[$key] === '') {
                continue;
            }

            $details[] = ['label' => $label, 'value' => $this->detailValue($key, $properties[$key])];
        }

        return $details;
    }

    private function subjectLabel(Activity $activity): ?string
    {
        return match (true) {
            $activity->subject instanceof Booking => $activity->subject->code,
            $activity->subject instanceof Car => "{$activity->subject->brand} {$activity->subject->model} ({$activity->subject->plate_number})",
            $activity->subject instanceof Employee => $activity->subject->name,
            $activity->subject instanceof User => $activity->subject->name,
            default => null,
        };
    }

    private function fieldLabel(string $field): string
    {
        $key = "validation.attributes.{$field}";

        return Lang::has($key) ? ucfirst((string) __($key)) : ucfirst(str_replace('_', ' ', $field));
    }

    private function value(string $field, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match (true) {
            $field === 'is_active' => $value ? 'Aktif' : 'Nonaktif',
            $field === 'status' => BookingStatus::tryFrom((string) $value)?->label() ?? (string) $value,
            $field === 'operational_status' => CarOperationalStatus::tryFrom((string) $value)?->label() ?? (string) $value,
            $field === 'role' => StaffRole::tryFrom((string) $value)?->label() ?? (string) $value,
            $field === 'employee_id' => $this->employees[(int) $value] ?? "Pegawai #{$value}",
            $field === 'car_id' => $this->cars[(int) $value] ?? "Mobil #{$value}",
            in_array($field, self::DATE_FIELDS, true) => $this->dateTime($value),
            is_bool($value) => $value ? 'Ya' : 'Tidak',
            is_scalar($value) => (string) $value,
            default => null,
        };
    }

    private function detailValue(string $key, mixed $value): string
    {
        return match (true) {
            $key === 'photo_source' => PhotoSource::tryFrom((string) $value)?->label() ?? (string) $value,
            $key === 'format' => ExportFormat::tryFrom((string) $value)?->label() ?? (string) $value,
            $key === 'via' && $value === 'artisan' => 'Perintah server (artisan)',
            is_bool($value) => $value ? 'Ya' : 'Tidak',
            is_scalar($value) => (string) $value,
            default => '',
        };
    }

    private function dateTime(mixed $value): string
    {
        try {
            return DateFormat::dateTime(CarbonImmutable::parse((string) $value));
        } catch (Throwable) {
            return (string) $value;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function changeSet(Activity $activity, string $side): array
    {
        /** @var Collection<string, mixed>|null $changes */
        $changes = $activity->attribute_changes;
        $values = $changes?->get($side);

        return is_array($values) ? $values : [];
    }
}
