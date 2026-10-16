<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Staff\ResetStaffPassword;
use App\Http\Controllers\Concerns\FlashesTemporaryPassword;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class StaffPasswordResetController extends Controller
{
    use FlashesTemporaryPassword;

    public function __invoke(Request $request, User $user, ResetStaffPassword $action): RedirectResponse
    {
        $credentials = $action->handle($user, $request->user());

        $this->flashTemporaryPassword($credentials, "Password {$user->name} berhasil direset.");

        return back();
    }
}
