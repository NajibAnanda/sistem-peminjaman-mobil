<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature test berjalan di database MySQL terpisah (peminjaman_mobil_test,
| lihat phpunit.xml) dan dikosongkan ulang setiap test.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function lastActivity(): Activity
{
    return Activity::query()->latest('id')->firstOrFail();
}
