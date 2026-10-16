<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Staff\UpdateOwnProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class ProfileController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('settings/profile');
    }

    public function update(ProfileUpdateRequest $request, UpdateOwnProfile $action): RedirectResponse
    {
        $action->handle($request->user(), $request->validated('name'));

        return to_route('profile.edit')->with('success', 'Profil berhasil diperbarui.');
    }
}
