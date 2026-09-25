<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidence_items', function (Blueprint $table) {
            $table->id();
            $table->string('qr_token')->unique();
            $table->string('no_reg_bb')->unique();
            $table->string('no_reg_perkara');
            $table->string('nama_terdakwa');
            $table->text('nama_barang');
            $table->string('jumlah_satuan');
            $table->string('lokasi_rak');
            $table->enum('status', [
                'TERSEDIA',
                'DIPINJAM_SIDANG',
                'PINJAM_PAKAI',
                'UJI_LAB_FORENSIK',
                'SELESAI - DIMUSNAHKAN',
                'SELESAI - DIKEMBALIKAN',
                'SELESAI - DILELANG_PNBP',
                'SELESAI - LAMPIR_BERKAS / PSP',
            ])->default('TERSEDIA');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence_items');
    }
};
