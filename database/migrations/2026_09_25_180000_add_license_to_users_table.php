<?php

use App\Models\User;
use App\Services\UserLicense;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('license_key', 40)->nullable()->unique()->after('is_active');
            $table->timestamp('license_issued_at')->nullable()->after('license_key');
            $table->timestamp('license_revoked_at')->nullable()->after('license_issued_at');
        });

        User::query()->whereNull('license_key')->each(function (User $user): void {
            $key = $user->email === 'admin@kejari-wajo.go.id'
                ? 'SITABA-ADMIN-WAJO-2026'
                : UserLicense::uniqueKey();

            $user->forceFill([
                'license_key' => $key,
                'license_issued_at' => now(),
            ])->saveQuietly();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['license_key']);
            $table->dropColumn(['license_key', 'license_issued_at', 'license_revoked_at']);
        });
    }
};
