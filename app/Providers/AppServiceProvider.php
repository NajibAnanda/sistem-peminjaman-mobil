<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

final class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configurePasswordPolicy();
        $this->configureAuthorization();
    }

    private function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        // Mencegah N+1 dan atribut yang hilang diam-diam selama pengembangan.
        Model::shouldBeStrict(! app()->isProduction());
    }

    private function configurePasswordPolicy(): void
    {
        // Kebijakan sama di semua environment (prd.md section 10.1). Cek password
        // bocor butuh internet, jadi hanya aktif bila diizinkan lewat .env.
        Password::defaults(function (): Password {
            $rule = Password::min((int) config('fleet.security.password_min_length'))
                ->letters()
                ->numbers();

            return config('fleet.security.password_check_uncompromised')
                ? $rule->uncompromised()
                : $rule;
        });
    }

    private function configureAuthorization(): void
    {
        // Diganti Policy pada model log aktivitas saat paketnya dipasang (Fase 2/6).
        Gate::define('view-activity-log', fn (User $user): bool => $user->isManager());
    }
}
