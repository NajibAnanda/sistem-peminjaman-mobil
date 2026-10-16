<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Staff\ChangeOwnPassword;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

final class TemporaryPasswordController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('auth/change-password', [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]);
    }

    public function update(PasswordUpdateRequest $request, ChangeOwnPassword $action): RedirectResponse
    {
        $action->handle($request->user(), $request->validated('password'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Password berhasil diganti.']);

        return to_route('dashboard');
    }
}
