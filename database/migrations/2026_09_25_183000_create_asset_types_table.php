<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('asset_types')) {
            Schema::create('asset_types', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 50)->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('physical_units', 'asset_type_id')) {
            Schema::table('physical_units', function (Blueprint $table) {
                $table->foreignId('asset_type_id')->nullable()->after('unit_type')->constrained('asset_types')->nullOnDelete();
            });
        }

        if (DB::table('asset_types')->count() === 0) {
            $now = now();

            foreach ([
                ['name' => 'Bergerak', 'code' => 'BERGERAK'],
                ['name' => 'Tidak Bergerak', 'code' => 'TIDAK_BERGERAK'],
                ['name' => 'Keuangan', 'code' => 'KEUANGAN'],
                ['name' => 'Surat Berharga', 'code' => 'SURAT_BERHARGA'],
                ['name' => 'Lainnya', 'code' => 'LAINNYA'],
            ] as $row) {
                DB::table('asset_types')->insert($row + ['is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('physical_units', 'asset_type_id')) {
            Schema::table('physical_units', function (Blueprint $table) {
                $table->dropConstrainedForeignId('asset_type_id');
            });
        }

        Schema::dropIfExists('asset_types');
    }
};
