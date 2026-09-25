<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('users.index', [
            'users' => User::query()->latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('users.create', [
            'roles' => UserRole::cases(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        $data['telegram_id'] = $data['telegram_id'] ?: null;

        $user = User::query()->create($data);

        return redirect()->route('users.index')->with([
            'status' => 'Pengguna berhasil ditambahkan. Berikan kode lisensi kepada yang bersangkutan.',
            'issued_license' => $user->license_key,
            'issued_license_user' => $user->name,
        ]);
    }

    public function edit(User $user): View
    {
        return view('users.edit', [
            'user' => $user,
            'roles' => UserRole::cases(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        $data['telegram_id'] = $data['telegram_id'] ?: null;

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('status', 'Pengguna diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_if($user->id === auth()->id(), 422, 'Tidak dapat menghapus akun sendiri.');

        $user->delete();

        return redirect()->route('users.index')->with('status', 'Pengguna dihapus.');
    }

    public function regenerateLicense(User $user): RedirectResponse
    {
        $user->issueLicense();

        return redirect()->route('users.index')->with([
            'status' => 'Lisensi baru diterbitkan. Kode lama tidak berlaku.',
            'issued_license' => $user->license_key,
            'issued_license_user' => $user->name,
        ]);
    }

    public function revokeLicense(User $user): RedirectResponse
    {
        abort_if($user->id === auth()->id(), 422, 'Tidak dapat mencabut lisensi akun sendiri.');

        $user->revokeLicense();

        return redirect()->route('users.index')->with('status', 'Lisensi '.$user->name.' dicabut. Akun ini tidak dapat masuk portal.');
    }

    public function restoreLicense(User $user): RedirectResponse
    {
        $user->restoreLicense();

        return redirect()->route('users.index')->with([
            'status' => 'Lisensi '.$user->name.' diaktifkan kembali.',
            'issued_license' => $user->license_key,
            'issued_license_user' => $user->name,
        ]);
    }
}
