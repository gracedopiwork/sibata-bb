<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_number', 100)->unique();
            $table->string('defendant_name')->index();
            $table->string('prosecutor_name');
            $table->enum('case_status', ['TAHAP_2', 'SIDANG', 'INKRACHT', 'SELESAI'])->default('TAHAP_2');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('physical_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->string('unit_code', 50)->unique();
            $table->enum('unit_type', ['SINGLE', 'PACK']);
            $table->string('storage_location');
            $table->string('photo_path')->nullable();
            $table->enum('current_status', ['TERSIMPAN_GUDANG', 'DIPINJAM_SIDANG', 'SELESAI'])->default('TERSIMPAN_GUDANG');
            $table->boolean('is_printed')->default(false);
            $table->timestamps();

            $table->index('current_status');
        });

        Schema::create('sip_evidence_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('physical_unit_id')->constrained('physical_units')->cascadeOnDelete();
            $table->string('item_name');
            $table->enum('category', ['NARKOTIKA', 'ELEKTRONIK', 'KENDARAAN', 'SENJATA', 'DOKUMEN', 'LAINNYA']);
            $table->string('quantity', 50);
            $table->enum('verdict_status', [
                'MENUNGGU_PUTUSAN',
                'DIMUSNAHKAN',
                'DIKEMBALIKAN',
                'DIRAMPAS_NEGARA_LELANG',
                'PSP',
            ])->default('MENUNGGU_PUTUSAN');
            $table->string('execution_ba_number', 100)->nullable();
            $table->string('execution_recipient')->nullable();
            $table->string('execution_recipient_nik', 20)->nullable();
            $table->date('execution_date')->nullable();
            $table->string('execution_proof_photo')->nullable();
            $table->timestamps();
        });

        Schema::create('mutations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('physical_unit_id')->constrained('physical_units')->cascadeOnDelete();
            $table->enum('mutation_type', ['CHECK_IN', 'PINJAM_SIDANG', 'KEMBALI_GUDANG', 'EKSEKUSI']);
            $table->string('borrower_name')->nullable();
            $table->date('court_date')->nullable();
            $table->text('notes')->nullable();
            $table->string('handled_by', 100);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('telegram_whitelist', function (Blueprint $table) {
            $table->id();
            $table->string('telegram_chat_id', 50)->unique();
            $table->string('user_name', 100);
            $table->enum('role', ['ADMIN_PB3R', 'JPU_PEGAWAI'])->default('JPU_PEGAWAI');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_whitelist');
        Schema::dropIfExists('mutations');
        Schema::dropIfExists('sip_evidence_items');
        Schema::dropIfExists('physical_units');
        Schema::dropIfExists('cases');
    }
};
