<?php

namespace Tests\Feature;

use App\Enums\TelegramAccessRole;
use App\Enums\UserRole;
use App\Models\AssetType;
use App\Models\CaseType;
use App\Models\PhysicalUnit;
use App\Models\Prosecutor;
use App\Models\StorageLocation;
use App\Models\TelegramWhitelist;
use App\Models\UnitItem;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SitabaSmokeTest extends TestCase
{
    public function test_login_page_is_visible(): void
    {
        $this->get('/login')->assertOk()->assertSee('SIBATA-BB');
    }

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_admin_can_open_dashboard_and_registers(): void
    {
        $admin = User::query()->where('email', 'admin@sibatabbwajo.my.id')->firstOrFail();

        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertSee('Dashboard PB3R');
        $this->actingAs($admin)->get('/cases')->assertOk()->assertSee('Register Perkara');
        $this->actingAs($admin)->get('/cases/create')->assertOk()->assertSee('Tambah ke daftar');
        $this->actingAs($admin)->get('/units')->assertOk()->assertSee('Inventaris Fisik');
        $this->actingAs($admin)->get('/items')->assertOk()->assertSee('Daftar Barang Bukti');
        $this->actingAs($admin)->get('/seals')->assertOk()->assertSee('Daftar Segel');
        $this->actingAs($admin)->get('/loans')->assertOk()->assertSee('Peminjaman BB');
        $this->actingAs($admin)->get('/loans/create')->assertOk()->assertSee('Foto saat dipinjam');
        $this->actingAs($admin)->get('/print-labels')->assertOk();
        $this->actingAs($admin)->get('/reports')->assertOk();
        $this->actingAs($admin)->get('/users')->assertOk();
        $this->actingAs($admin)->get('/prosecutors')->assertOk()->assertSee('Data Master JPU');
        $this->actingAs($admin)->get('/case-types')->assertOk()->assertSee('Jenis Perkara');
        $this->actingAs($admin)->get('/asset-types')->assertOk()->assertSee('Jenis Aset');
        $this->actingAs($admin)->get('/evidence-categories')->assertOk()->assertSee('Jenis BB');
        $this->actingAs($admin)->get('/storage-locations')->assertOk()->assertSee('Tempat Penyimpanan');
        $this->actingAs($admin)->get('/whitelist')->assertOk();
        $this->actingAs($admin)->get('/bot')->assertOk()->assertSee('BotFather');
    }

    public function test_petugas_cannot_open_dashboard(): void
    {
        $petugas = User::query()->where('email', 'petugas@kejari-wajo.go.id')->firstOrFail();

        $this->actingAs($petugas)->get('/dashboard')->assertForbidden();
        $this->actingAs($petugas)->get('/cases')->assertForbidden();
        $this->actingAs($petugas)->get('/users')->assertForbidden();
    }

    public function test_non_admin_cannot_login_to_dashboard(): void
    {
        $petugas = User::query()->where('email', 'petugas@kejari-wajo.go.id')->firstOrFail();

        $this->post('/login', [
            'email' => $petugas->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_can_login_without_license(): void
    {
        $admin = User::query()->where('email', 'admin@sibatabbwajo.my.id')->firstOrFail();

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_creating_user_issues_license(): void
    {
        $admin = User::query()->where('email', 'admin@sibatabbwajo.my.id')->firstOrFail();
        $email = 'lisensi.uji.'.now()->format('Hisu').'@kejari-wajo.go.id';

        $response = $this->actingAs($admin)->post('/users', [
            'name' => 'Admin Lisensi Uji',
            'email' => $email,
            'nip' => '198001012006031099',
            'role' => 'admin',
            'is_active' => '1',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect('/users');
        $this->assertNotEmpty(session('issued_license'));

        $created = User::query()->where('email', $email)->first();

        $this->assertNotEmpty($created?->license_key);
        $this->assertTrue($created?->hasValidLicense());
        $this->assertStringStartsWith('SIBATA-', (string) $created?->license_key);

        $created?->delete();
    }

    public function test_adding_telegram_access_creates_a_user_and_deleting_user_removes_both(): void
    {
        $admin = User::query()->where('email', 'admin@sibatabbwajo.my.id')->firstOrFail();

        $this->actingAs($admin)->post('/whitelist', [
            'user_name' => 'Syawal',
            'telegram_chat_id' => '17947501477',
            'role' => TelegramAccessRole::AdminPb3r->value,
            'is_active' => '1',
        ])->assertRedirect('/whitelist');

        $created = User::query()->where('telegram_id', 17947501477)->first();

        $this->assertNotNull($created);
        $this->assertTrue($created->hasValidLicense());

        $this->actingAs($admin)->delete('/users/'.$created->id)->assertRedirect('/users');

        $this->assertDatabaseMissing('users', ['telegram_id' => 17947501477]);
        $this->assertDatabaseMissing('telegram_whitelist', ['telegram_chat_id' => '17947501477']);

        $this->actingAs($admin)->get('/users')->assertOk()->assertDontSee('Syawal');
    }

    public function test_bot_unknown_name_does_not_open_license_and_known_name_requires_matching_key(): void
    {
        $target = User::factory()->create([
            'name' => 'Syawal Uji',
            'role' => UserRole::PetugasPb3r,
            'is_active' => true,
            'password' => 'password',
        ]);
        $other = User::factory()->create([
            'name' => 'Petugas Lain',
            'role' => UserRole::PetugasPb3r,
            'is_active' => true,
            'password' => 'password',
        ]);

        $this->postJson('/api/telegram/webhook', [
            'message' => [
                'from' => ['id' => 888111222, 'first_name' => 'Tamu'],
                'chat' => ['id' => 888111222],
                'text' => '/start',
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook', [
            'message' => [
                'from' => ['id' => 888111222, 'first_name' => 'Tamu'],
                'chat' => ['id' => 888111222],
                'text' => 'Nama Tidak Ada',
            ],
        ])->assertOk();

        $this->assertDatabaseMissing('users', ['telegram_id' => 888111222]);

        $this->postJson('/api/telegram/webhook', [
            'message' => [
                'from' => ['id' => 888111222, 'first_name' => 'Tamu'],
                'chat' => ['id' => 888111222],
                'text' => 'Syawal Uji',
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook', [
            'message' => [
                'from' => ['id' => 888111222, 'first_name' => 'Tamu'],
                'chat' => ['id' => 888111222],
                'text' => $other->license_key,
            ],
        ])->assertOk();

        $this->assertDatabaseMissing('users', ['telegram_id' => 888111222]);

        $this->postJson('/api/telegram/webhook', [
            'message' => [
                'from' => ['id' => 888111222, 'first_name' => 'Tamu'],
                'chat' => ['id' => 888111222],
                'text' => $target->license_key,
            ],
        ])->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'telegram_id' => 888111222,
        ]);

        $target->delete();
        $other->delete();
        TelegramWhitelist::query()->where('telegram_chat_id', '888111222')->delete();
    }

    public function test_bot_license_can_be_redeemed_and_revoked(): void
    {
        User::query()->where('telegram_id', 555666777)->update(['telegram_id' => null]);
        TelegramWhitelist::query()->where('telegram_chat_id', '555666777')->delete();

        $admin = User::query()->where('email', 'admin@sibatabbwajo.my.id')->firstOrFail();
        $target = User::factory()->create([
            'role' => UserRole::PetugasPb3r,
            'is_active' => true,
            'password' => 'password',
        ]);

        $this->postJson('/api/telegram/webhook', [
            'message' => [
                'from' => ['id' => 555666777, 'first_name' => 'Petugas'],
                'chat' => ['id' => 555666777],
                'text' => '/lisensi '.$target->license_key,
            ],
        ])->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'telegram_id' => 555666777,
        ]);
        $this->assertDatabaseHas('telegram_whitelist', [
            'telegram_chat_id' => '555666777',
            'is_active' => 1,
        ]);

        $this->actingAs($admin)
            ->post(route('users.license.revoke', $target))
            ->assertRedirect('/users');

        $this->assertFalse($target->fresh()->hasValidLicense());
        $this->assertDatabaseHas('telegram_whitelist', [
            'telegram_chat_id' => '555666777',
            'is_active' => 0,
        ]);

        $target->delete();
    }

    public function test_public_qr_view_is_visible(): void
    {
        $unit = PhysicalUnit::query()->firstOrFail();

        $this->get('/view/'.$unit->unit_code)
            ->assertOk()
            ->assertSee($unit->unit_code)
            ->assertSee($unit->legalCase?->defendant_name);
    }

    public function test_admin_can_register_case_with_single_and_pack(): void
    {
        $admin = User::query()->where('email', 'admin@sibatabbwajo.my.id')->firstOrFail();

        $caseNumber = 'REG-TEST/PID.SUS/'.now()->format('His');

        $response = $this->actingAs($admin)->post('/cases', [
            'case_number' => $caseNumber,
            'defendant_name' => 'La Ode Tes',
            'case_type_id' => CaseType::query()->where('code', 'NARKOTIKA')->value('id'),
            'prosecutor_ids' => [Prosecutor::query()->firstOrFail()->id],
            'units' => [
                [
                    'type' => 'SINGLE',
                    'asset_type_id' => AssetType::query()->where('code', 'BERGERAK')->value('id'),
                    'item_name' => '1 unit parang gagang kayu '.$caseNumber,
                    'category' => 'SENJATA',
                    'quantity' => '1 unit',
                    'storage_location_id' => StorageLocation::query()->where('code', 'LEMARI_SENJATA')->value('id'),
                ],
                [
                    'type' => 'PACK',
                    'asset_type_id' => AssetType::query()->where('code', 'BERGERAK')->value('id'),
                    'storage_location_id' => StorageLocation::query()->where('code', 'BRANKAS_02')->value('id'),
                    'children' => [
                        [
                            'item_name' => '1 sachet sabu 0,3 gram '.$caseNumber,
                            'category' => 'NARKOTIKA',
                            'quantity' => '1 sachet',
                        ],
                        [
                            'item_name' => '1 unit HP Android',
                            'category' => 'ELEKTRONIK',
                            'quantity' => '1 unit',
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cases', ['case_number' => $caseNumber]);
        $this->assertDatabaseHas('physical_units', [
            'storage_location' => 'Lemari Senjata',
            'storage_location_id' => StorageLocation::query()->where('code', 'LEMARI_SENJATA')->value('id'),
        ]);
        $this->assertDatabaseHas('sip_evidence_items', ['item_name' => '1 sachet sabu 0,3 gram '.$caseNumber]);
    }

    public function test_admin_can_register_sealed_pack_from_bulk_list(): void
    {
        $admin = User::query()->where('email', 'admin@sibatabbwajo.my.id')->firstOrFail();
        $caseNumber = 'REG-BULK/PID.SUS/'.now()->format('His');

        $this->actingAs($admin)->post('/cases', [
            'case_number' => $caseNumber,
            'defendant_name' => 'La Ode Bulk',
            'case_type_id' => CaseType::query()->where('code', 'NARKOTIKA')->value('id'),
            'prosecutor_ids' => [Prosecutor::query()->firstOrFail()->id],
            'units' => [[
                'type' => 'PACK',
                'asset_type_id' => AssetType::query()->where('code', 'BERGERAK')->value('id'),
                'storage_location_id' => StorageLocation::query()->where('code', 'BRANKAS_02')->value('id'),
                'contents_bulk' => "2 sachet sabu 0,4 gram {$caseNumber}\n1 unit HP Android | ELEKTRONIK | 1 unit",
            ]],
        ])->assertRedirect();

        $this->assertDatabaseHas('sip_evidence_items', ['item_name' => '2 sachet sabu 0,4 gram '.$caseNumber]);
        $this->assertDatabaseHas('sip_evidence_items', ['item_name' => '1 unit HP Android']);
    }

    public function test_admin_can_save_sealed_pack_without_contents(): void
    {
        $admin = User::query()->where('email', 'admin@sibatabbwajo.my.id')->firstOrFail();
        $caseNumber = 'REG-SEGEL/PID.SUS/'.now()->format('His');

        $this->actingAs($admin)->post('/cases', [
            'case_number' => $caseNumber,
            'defendant_name' => 'La Ode Segel',
            'case_type_id' => CaseType::query()->where('code', 'NARKOTIKA')->value('id'),
            'prosecutor_ids' => [Prosecutor::query()->firstOrFail()->id],
            'units' => [[
                'type' => 'PACK',
                'asset_type_id' => AssetType::query()->where('code', 'BERGERAK')->value('id'),
                'storage_location_id' => StorageLocation::query()->where('code', 'BRANKAS_01')->value('id'),
            ]],
        ])->assertRedirect();

        $this->assertDatabaseHas('cases', ['case_number' => $caseNumber]);
        $this->assertDatabaseHas('physical_units', [
            'storage_location' => 'Brankas PB3R Laci 01',
            'unit_type' => 'PACK',
        ]);
    }

    public function test_admin_can_store_unit_photo_in_database(): void
    {
        Storage::fake('public');
        $admin = User::query()->where('email', 'admin@sibatabbwajo.my.id')->firstOrFail();
        $unit = PhysicalUnit::query()->firstOrFail();

        $this->actingAs($admin)->post(route('units.photo.update', $unit), [
            'photo' => UploadedFile::fake()->image('segel.jpg', 200, 200),
        ])->assertRedirect();

        $this->assertDatabaseHas('unit_photos', ['physical_unit_id' => $unit->id]);
        $this->get(route('units.photo', $unit))->assertOk();
    }

    public function test_admin_can_loan_and_return_unit(): void
    {
        $admin = User::query()->where('email', 'admin@sibatabbwajo.my.id')->firstOrFail();
        $unit = PhysicalUnit::query()->where('current_status', 'TERSIMPAN_GUDANG')->firstOrFail();

        Storage::fake('public');

        $this->actingAs($admin)->post(route('units.loan', $unit), [
            'prosecutor_ids' => [Prosecutor::query()->firstOrFail()->id],
            'court_date' => now()->addDay()->toDateString(),
            'notes' => 'Sidang pembuktian',
            'photo' => UploadedFile::fake()->image('pinjam.jpg', 200, 200),
        ])->assertRedirect();

        $this->assertDatabaseHas('physical_units', [
            'id' => $unit->id,
            'current_status' => 'DIPINJAM_SIDANG',
        ]);
        $this->assertDatabaseHas('bb_loans', [
            'physical_unit_id' => $unit->id,
            'returned_at' => null,
        ]);

        $this->actingAs($admin)->post(route('units.return', $unit), [
            'storage_location_id' => $unit->storage_location_id ?? StorageLocation::query()->firstOrFail()->id,
            'notes' => 'Kondisi baik',
            'photo' => UploadedFile::fake()->image('kembali.jpg', 200, 200),
        ])->assertRedirect();

        $this->assertDatabaseHas('physical_units', [
            'id' => $unit->id,
            'current_status' => 'TERSIMPAN_GUDANG',
        ]);

        $item = $unit->items()->first();
        if ($item !== null) {
            $this->actingAs($admin)
                ->get(route('items.show', $item))
                ->assertOk()
                ->assertSee('Daftar peminjaman BB')
                ->assertSee('Tanggal dipinjam')
                ->assertSee('Tanggal dikembalikan');
        }

        if ($unit->legalCase) {
            $this->actingAs($admin)
                ->get(route('cases.show', $unit->legalCase))
                ->assertOk()
                ->assertSee('Peminjaman BB perkara ini')
                ->assertSee($unit->unit_code)
                ->assertSee('Sudah dikembalikan');
        }
    }

    public function test_print_sheet_renders_for_admin(): void
    {
        $admin = User::query()->where('email', 'admin@sibatabbwajo.my.id')->firstOrFail();
        $unit = PhysicalUnit::query()->firstOrFail();

        $this->actingAs($admin)
            ->get(route('print-labels.sheet', ['ids' => $unit->id]))
            ->assertOk()
            ->assertSee('KEJAKSAAN NEGERI WAJO')
            ->assertSee($unit->unit_code);
    }

    public function test_already_printed_unit_can_be_reprinted(): void
    {
        $admin = User::query()->where('email', 'admin@sibatabbwajo.my.id')->firstOrFail();
        $unit = PhysicalUnit::query()->firstOrFail();
        $unit->update(['is_printed' => true]);

        $this->actingAs($admin)
            ->get(route('units.show', $unit))
            ->assertOk()
            ->assertSee('Cetak ulang');

        $this->actingAs($admin)
            ->get(route('print-labels.sheet', ['ids' => $unit->id]))
            ->assertOk()
            ->assertSee($unit->unit_code);
    }

    public function test_telegram_webhook_accepts_unauthorized_update(): void
    {
        $this->postJson('/api/telegram/webhook', [
            'message' => [
                'from' => ['id' => 999999999],
                'chat' => ['id' => 999999999],
                'text' => '/start',
            ],
        ])->assertOk();
    }

    public function test_telegram_webhook_accepts_group_updates(): void
    {
        $this->postJson('/api/telegram/webhook', [
            'message' => [
                'message_id' => 10,
                'from' => ['id' => 999999999],
                'chat' => ['id' => -1001234567890, 'type' => 'supergroup', 'title' => 'PB3R Wajo'],
                'text' => 'obrolan biasa',
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook', [
            'my_chat_member' => [
                'chat' => ['id' => -1001234567890, 'type' => 'supergroup', 'title' => 'PB3R Wajo'],
                'new_chat_member' => ['status' => 'member'],
            ],
        ])->assertOk();
    }
}
