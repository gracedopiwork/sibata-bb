<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ListUserLicensesCommand extends Command
{
    protected $signature = 'user:licenses';

    protected $description = 'Tampilkan kode lisensi portal per pengguna';

    public function handle(): int
    {
        $rows = User::query()
            ->orderBy('name')
            ->get()
            ->map(fn (User $user): array => [
                $user->name,
                $user->email,
                $user->license_key ?: '—',
                $user->hasValidLicense() ? 'berlaku' : 'dicabut',
                $user->role->label(),
            ]);

        $this->table(['Nama', 'Email', 'Lisensi', 'Status', 'Peran'], $rows);

        return self::SUCCESS;
    }
};
