<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Staff\CreateStaffAccount;
use App\Actions\Staff\UpdateStaffAccount;
use App\Enums\StaffRole;
use App\Http\Controllers\Concerns\FlashesTemporaryPassword;
use App\Http\Requests\Staff\StaffIndexRequest;
use App\Http\Requests\Staff\StoreStaffRequest;
use App\Http\Requests\Staff\UpdateStaffRequest;
use App\Http\Resources\StaffResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class StaffController extends Controller
{
    use FlashesTemporaryPassword;

    public function index(StaffIndexRequest $request): Response
    {
        $filters = $request->filters();

        $staff = User::query()
            ->search($filters['search'])
            ->when($filters['role'], fn (Builder $query, string $role) => $query->where('role', $role))
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where(
                'is_active',
                $status === StaffIndexRequest::STATUS_ACTIVE,
            ))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate((int) config('fleet.per_page'))
            ->withQueryString();

        return Inertia::render('staff/index', [
            'staff' => StaffResource::collection($staff),
            'filters' => $filters,
            'roles' => array_map(
                fn (StaffRole $role): array => ['value' => $role->value, 'label' => $role->label()],
                StaffRole::cases(),
            ),
        ]);
    }

    public function store(StoreStaffRequest $request, CreateStaffAccount $action): RedirectResponse
    {
        $credentials = $action->handle($request->staffData(), $request->user());

        $this->flashTemporaryPassword($credentials, 'Akun petugas berhasil dibuat.');

        return to_route('staff.index');
    }

    public function update(UpdateStaffRequest $request, User $user, UpdateStaffAccount $action): RedirectResponse
    {
        $action->handle($user, $request->staffData(), $request->user());

        return back()->with('success', 'Akun petugas berhasil diperbarui.');
    }
}
