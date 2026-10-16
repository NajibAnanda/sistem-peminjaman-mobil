<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Staff\DeactivateStaffAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class StaffDeactivationController extends Controller
{
    public function __invoke(Request $request, User $user, DeactivateStaffAccount $action): RedirectResponse
    {
        $action->handle($user, $request->user());

        return back()->with('success', "Akun {$user->name} dinonaktifkan dan langsung keluar dari aplikasi.");
    }
}
