<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\PhysicalUnit;
use App\Models\User;
use Tests\TestCase;

class SitabaSmokeTest extends TestCase
{
    public function test_login_page_is_visible(): void
    {
        $this->get('/login')->assertOk()->assertSee('SITABA-BB');
    }

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_admin_can_open_dashboard_and_registers(): void
    {
        $admin = User::query()->where('email', 'admin@kejari-wajo.go.id')->firstOrFail();

        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertSee('Dashboard PB3R');
        $this->actingAs($admin)->get('/cases')->assertOk()->assertSee('Register Perkara');
        $this->actingAs($admin)->get('/units')->assertOk()->assertSee('Inventaris Fisik');
        $this->actingAs($admin)->get('/print-labels')->assertOk();
        $this->actingAs($admin)->get('/reports')->assertOk();
        $this->actingAs($admin)->get('/users')->assertOk();
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
            'license_key' => $petugas->license_key,
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_login_requires_valid_license(): void
    {
        $admin = User::query()->where('email', 'admin@kejari-wajo.go.id')->firstOrFail();

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertSessionHasErrors('license_key');

        $this->assertGuest();

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
            'license_key' => 'SITABA-SALAH-XXXX-XXXX',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
            'license_key' => $admin->license_key,
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_creating_user_issues_license(): void
    {
        $admin = User::query()->where('email', 'admin@kejari-wajo.go.id')->firstOrFail();
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
        $this->assertStringStartsWith('SITABA-', (string) $created?->license_key);

        $created?->delete();
    }

    public function test_revoked_license_cannot_login(): void
    {
        $admin = User::query()->where('email', 'admin@kejari-wajo.go.id')->firstOrFail();
        $target = User::factory()->create([
            'role' => UserRole::Admin,
            'is_active' => true,
            'password' => 'password',
        ]);

        $this->actingAs($admin)
            ->post(route('users.license.revoke', $target))
            ->assertRedirect('/users');

        $this->assertFalse($target->fresh()->hasValidLicense());

        $this->post('/logout');

        $this->post('/login', [
            'email' => $target->email,
            'password' => 'password',
            'license_key' => $target->license_key,
        ])->assertSessionHasErrors('email');

        $this->assertGuest();

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
        $admin = User::query()->where('email', 'admin@kejari-wajo.go.id')->firstOrFail();

        $caseNumber = 'REG-TEST/PID.SUS/'.now()->format('His');

        $response = $this->actingAs($admin)->post('/cases', [
            'case_number' => $caseNumber,
            'defendant_name' => 'La Ode Tes',
            'prosecutor_name' => 'JPU Uji, S.H.',
            'units' => [
                [
                    'type' => 'SINGLE',
                    'item_name' => '1 unit parang gagang kayu '.$caseNumber,
                    'category' => 'SENJATA',
                    'quantity' => '1 unit',
                    'storage_location' => 'Lemari Senjata '.$caseNumber,
                ],
                [
                    'type' => 'PACK',
                    'storage_location' => 'Brankas PB3R Laci 09',
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
        $this->assertDatabaseHas('physical_units', ['storage_location' => 'Lemari Senjata '.$caseNumber]);
        $this->assertDatabaseHas('sip_evidence_items', ['item_name' => '1 sachet sabu 0,3 gram '.$caseNumber]);
    }

    public function test_admin_can_loan_and_return_unit(): void
    {
        $admin = User::query()->where('email', 'admin@kejari-wajo.go.id')->firstOrFail();
        $unit = PhysicalUnit::query()->where('current_status', 'TERSIMPAN_GUDANG')->firstOrFail();

        $this->actingAs($admin)->post(route('units.loan', $unit), [
            'borrower_name' => 'JPU Tes Pinjam',
            'court_date' => now()->addDay()->toDateString(),
            'notes' => 'Sidang pembuktian',
        ])->assertRedirect();

        $this->assertDatabaseHas('physical_units', [
            'id' => $unit->id,
            'current_status' => 'DIPINJAM_SIDANG',
        ]);

        $this->actingAs($admin)->post(route('units.return', $unit), [
            'storage_location' => $unit->storage_location,
            'notes' => 'Kondisi baik',
        ])->assertRedirect();

        $this->assertDatabaseHas('physical_units', [
            'id' => $unit->id,
            'current_status' => 'TERSIMPAN_GUDANG',
        ]);
    }

    public function test_print_sheet_renders_for_admin(): void
    {
        $admin = User::query()->where('email', 'admin@kejari-wajo.go.id')->firstOrFail();
        $unit = PhysicalUnit::query()->firstOrFail();

        $this->actingAs($admin)
            ->get(route('print-labels.sheet', ['ids' => $unit->id]))
            ->assertOk()
            ->assertSee('KEJAKSAAN NEGERI WAJO')
            ->assertSee($unit->unit_code);
    }

    public function test_already_printed_unit_can_be_reprinted(): void
    {
        $admin = User::query()->where('email', 'admin@kejari-wajo.go.id')->firstOrFail();
        $unit = PhysicalUnit::query()->firstOrFail();
        $unit->update(['is_printed' => true]);

        $this->actingAs($admin)
            ->get('/units')
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
