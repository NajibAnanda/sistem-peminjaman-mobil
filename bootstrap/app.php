<?php

use App\Exceptions\BusinessRuleException;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsureTwoFactorEnabled;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->append(SecurityHeaders::class);

        // Urutan penting: akun nonaktif dikeluarkan lebih dulu, lalu password
        // sementara, baru kewajiban 2FA Pengelola.
        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            EnsureAccountIsActive::class,
            EnsurePasswordChanged::class,
            EnsureTwoFactorEnabled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Pelanggaran aturan bisnis bukan error sistem: tampilkan pesannya
        // sebagai toast dan kembali ke halaman sebelumnya.
        $exceptions->dontReport(BusinessRuleException::class);
        $exceptions->render(function (BusinessRuleException $exception, Request $request) {
            $field = $exception->field();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $exception->getMessage(),
                    'errors' => $field === null ? [] : [$field => [$exception->getMessage()]],
                ], 422);
            }

            // Terkait satu field form (mis. bentrok jadwal pada pilihan mobil):
            // tampil di bawah field agar form tetap terbuka dan isinya tidak hilang.
            if ($field !== null) {
                return back()->withErrors([$field => $exception->getMessage()])->withInput();
            }

            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return back();
        });
    })->create();
