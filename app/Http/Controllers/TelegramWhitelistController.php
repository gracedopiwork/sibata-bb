<?php

namespace App\Http\Controllers;

use App\Enums\TelegramAccessRole;
use App\Http\Requests\StoreTelegramWhitelistRequest;
use App\Http\Requests\UpdateTelegramWhitelistRequest;
use App\Models\TelegramWhitelist;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TelegramWhitelistController extends Controller
{
    public function index(): View
    {
        return view('whitelist.index', [
            'entries' => TelegramWhitelist::query()->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('whitelist.create', [
            'roles' => TelegramAccessRole::cases(),
        ]);
    }

    public function store(StoreTelegramWhitelistRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        TelegramWhitelist::query()->create($data);

        return redirect()->route('whitelist.index')->with('status', 'Akses Telegram ditambahkan.');
    }

    public function edit(TelegramWhitelist $whitelist): View
    {
        return view('whitelist.edit', [
            'entry' => $whitelist,
            'roles' => TelegramAccessRole::cases(),
        ]);
    }

    public function update(UpdateTelegramWhitelistRequest $request, TelegramWhitelist $whitelist): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $whitelist->update($data);

        return redirect()->route('whitelist.index')->with('status', 'Akses Telegram diperbarui.');
    }

    public function destroy(TelegramWhitelist $whitelist): RedirectResponse
    {
        User::query()
            ->where('telegram_id', $whitelist->telegram_chat_id)
            ->update(['telegram_id' => null]);

        $whitelist->delete();

        return redirect()->route('whitelist.index')->with('status', 'Akses Telegram dihapus. Orang itu harus daftar ulang: nama lalu kode lisensi.');
    }
}
