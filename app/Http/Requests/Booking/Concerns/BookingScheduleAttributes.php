<?php

declare(strict_types=1);

namespace App\Http\Requests\Booking\Concerns;

use App\Models\Car;
use App\Models\Employee;
use App\Support\BookingDuration;
use App\Support\DateFormat;
use App\Support\DateInput;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Validator;
use Throwable;

trait BookingScheduleAttributes
{
    /**
     * @return array<string, array<int, string|Exists>>
     */
    protected function scheduleRules(bool $checkStartTolerance): array
    {
        $startRules = ['required', 'date'];

        // AGENTS.md 5.11: waktu mulai tidak boleh di masa lalu, kecuali dalam
        // toleransi untuk karyawan yang datang langsung.
        if ($checkStartTolerance) {
            $startRules[] = 'after_or_equal:'.$this->earliestStart()->format('Y-m-d H:i:s');
        }

        return [
            'employee_id' => ['required', 'integer', Rule::exists(Employee::class, 'id')],
            'car_id' => ['required', 'integer', Rule::exists(Car::class, 'id')],
            'start_at' => $startRules,
            'planned_return_at' => ['required', 'date', 'after:start_at'],
            'purpose' => ['required', 'string', 'min:'.config('fleet.booking.purpose_min_length'), 'max:255'],
        ];
    }

    /**
     * Durasi min./maks. (config/fleet.php) dicek setelah format tanggal lolos.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['start_at', 'planned_return_at'])) {
                    return;
                }

                try {
                    $message = BookingDuration::violation($this->dateTimeInput('start_at'), $this->dateTimeInput('planned_return_at'));
                } catch (Throwable) {
                    return;
                }

                if ($message !== null) {
                    $validator->errors()->add('planned_return_at', $message);
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $tolerance = (int) config('fleet.booking.start_tolerance_minutes');

        return [
            'start_at.after_or_equal' => "Waktu mulai tidak boleh lebih dari {$tolerance} menit yang lalu (paling awal ".DateFormat::dateTime($this->earliestStart()).').',
            'planned_return_at.after' => 'Rencana waktu kembali harus setelah waktu mulai.',
        ];
    }

    /**
     * @return array{employee_id: int, car_id: int, start_at: CarbonImmutable, planned_return_at: CarbonImmutable, purpose: string}
     */
    public function scheduleData(): array
    {
        return [
            'employee_id' => $this->integer('employee_id'),
            'car_id' => $this->integer('car_id'),
            'start_at' => $this->dateTimeInput('start_at'),
            'planned_return_at' => $this->dateTimeInput('planned_return_at'),
            'purpose' => trim($this->string('purpose')->value()),
        ];
    }

    private function earliestStart(): CarbonImmutable
    {
        return CarbonImmutable::now()->subMinutes((int) config('fleet.booking.start_tolerance_minutes'));
    }

    private function dateTimeInput(string $key): CarbonImmutable
    {
        return DateInput::parse($this->string($key)->value());
    }
}
