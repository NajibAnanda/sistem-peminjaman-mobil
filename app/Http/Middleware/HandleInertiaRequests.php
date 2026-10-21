<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\BookingAlerts;
use App\Support\BookingDuration;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Middleware;
use Symfony\Component\HttpFoundation\Response;

final class HandleInertiaRequests extends Middleware
{
    /**
     * Jenis flash message Laravel yang ditampilkan sebagai toast.
     *
     * @var list<string>
     */
    private const TOAST_TYPES = ['success', 'info', 'warning', 'error'];

    protected $rootView = 'app';

    public function handle(Request $request, Closure $next): Response
    {
        $this->flashSessionMessagesAsToast($request);

        return parent::handle($request, $next);
    }

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'timezone' => config('app.timezone'),
            'auth' => [
                'user' => $user instanceof User ? $this->sharedUser($user) : null,
                'can' => [
                    'manageStaff' => $user?->can('viewAny', User::class) ?? false,
                    'viewActivityLog' => $user?->can('view-activity-log') ?? false,
                ],
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            // Closure: hanya dihitung saat dibutuhkan, dan dapat dimuat ulang
            // sendirian lewat polling (only: ['alerts']).
            'alerts' => fn (): ?array => $user instanceof User ? BookingAlerts::counts() : null,
            'pollingIntervalSeconds' => (int) config('fleet.polling_interval_seconds'),
            'minuteStep' => (int) config('fleet.date_picker.minute_step'),
            // Hanya untuk petunjuk di form; validasi tetap di server.
            'bookingRules' => [
                'purposeMinLength' => (int) config('fleet.booking.purpose_min_length'),
                'reasonMinLength' => (int) config('fleet.booking.reason_min_length'),
                'minDurationMinutes' => (int) config('fleet.booking.min_duration_minutes'),
                'maxDurationDays' => BookingDuration::maxDays(),
                'longBookingWarningDays' => (int) config('fleet.booking.long_booking_warning_days'),
            ],
        ];
    }

    /**
     * Hanya field yang dibutuhkan antarmuka; jangan kirim model utuh.
     *
     * @return array<string, mixed>
     */
    private function sharedUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'role_label' => $user->role->label(),
            'two_factor_enabled' => $user->hasConfirmedTwoFactor(),
        ];
    }

    /**
     * Controller boleh memakai `->with('success', '...')` seperti contoh di
     * AGENTS.md; pesannya diteruskan ke toast Inertia di sini.
     */
    private function flashSessionMessagesAsToast(Request $request): void
    {
        if (! $request->hasSession()) {
            return;
        }

        foreach (self::TOAST_TYPES as $type) {
            $message = $request->session()->get($type);

            if (is_string($message) && $message !== '') {
                Inertia::flash('toast', ['type' => $type, 'message' => $message]);
            }
        }
    }
}
