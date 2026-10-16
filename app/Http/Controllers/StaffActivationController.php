<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Staff\ActivateStaffAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class StaffActivationController extends Controller
{
    public function __invoke(Request $request, User $user, ActivateStaffAccount $action): RedirectResponse
    {
        $action->handle($user, $request->user());

        return back()->with('success', "Akun {$user->name} diaktifkan kembali.");
    }
}
