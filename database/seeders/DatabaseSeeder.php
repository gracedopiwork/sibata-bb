<?php

namespace Database\Seeders;

use App\Enums\ItemCategory;
use App\Enums\TelegramAccessRole;
use App\Enums\UserRole;
use App\Models\LegalCase;
use App\Models\TelegramWhitelist;
use App\Models\User;
use App\Services\WarehouseService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@kejari-wajo.go.id'],
            [
                'name' => 'Admin PB3R',
                'nip' => '199001012015031001',
                'role' => UserRole::Admin,
                'is_active' => true,
                'password' => Hash::make('password'),
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'petugas@kejari-wajo.go.id'],
            [
                'name' => 'Petugas Gudang PB3R',
                'nip' => '199203152018032002',
                'role' => UserRole::PetugasPb3r,
                'is_active' => true,
                'password' => Hash::make('password'),
            ]
        );

        TelegramWhitelist::query()->updateOrCreate(
            ['telegram_chat_id' => '1001'],
            [
                'user_name' => 'Petugas Gudang PB3R',
                'role' => TelegramAccessRole::AdminPb3r,
                'is_active' => true,
            ]
        );

        if (LegalCase::query()->exists()) {
            return;
        }

        $warehouse = app(WarehouseService::class);

        $narkotika = LegalCase::query()->create([
            'case_number' => 'REG-012/PID.SUS/2026',
            'defendant_name' => 'Hasanuddin',
            'prosecutor_name' => 'JPU Andi Fajar, S.H.',
        ]);

        $warehouse->createSingleUnit(
            $narkotika,
            '1 unit sepeda motor Honda Beat warna hitam, nopol DP 3456 XY',
            ItemCategory::Kendaraan,
            '1 unit',
            'Parkiran BB No. 04',
            null,
            'Admin PB3R',
        );

        $warehouse->createPackUnit(
            $narkotika,
            'Brankas PB3R Laci 02',
            null,
            'Admin PB3R',
            [
                ['item_name' => '2 sachet sabu kristal 0,5 gram', 'category' => ItemCategory::Narkotika, 'quantity' => '2 sachet'],
                ['item_name' => '1 unit timbangan digital', 'category' => ItemCategory::Elektronik, 'quantity' => '1 unit'],
                ['item_name' => 'HP Vivo Y21', 'category' => ItemCategory::Elektronik, 'quantity' => '1 unit'],
            ],
        );

        $tipikor = LegalCase::query()->create([
            'case_number' => 'REG-021/PID.SUS-TPK/2026',
            'defendant_name' => 'Hj. Sitti Aminah',
            'prosecutor_name' => 'JPU Muhammad Yusuf, S.H., M.H.',
        ]);

        $warehouse->createSingleUnit(
            $tipikor,
            '1 unit laptop ASUS VivoBook beserta charger',
            ItemCategory::Elektronik,
            '1 unit',
            'Lemari Besi A-02',
            null,
            'Admin PB3R',
        );
    }
}
