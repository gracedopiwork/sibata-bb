<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nip')->nullable()->after('email');
            $table->unsignedBigInteger('telegram_id')->nullable()->unique()->after('nip');
            $table->enum('role', ['admin', 'petugas_pb3r', 'jpu'])->default('petugas_pb3r')->after('telegram_id');
            $table->boolean('is_active')->default(true)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['telegram_id']);
            $table->dropColumn(['nip', 'telegram_id', 'role', 'is_active']);
        });
    }
};
