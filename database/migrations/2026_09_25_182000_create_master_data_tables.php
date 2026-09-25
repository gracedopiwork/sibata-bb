<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('prosecutors')) {
        Schema::create('prosecutors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('nip', 30)->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        }

        if (! Schema::hasTable('case_types')) {
        Schema::create('case_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        }

        if (! Schema::hasTable('evidence_categories')) {
        Schema::create('evidence_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        }

        if (! Schema::hasTable('case_prosecutor')) {
        Schema::create('case_prosecutor', function (Blueprint $table) {
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('prosecutor_id')->constrained('prosecutors')->restrictOnDelete();
            $table->primary(['case_id', 'prosecutor_id']);
        });
        }

        if (! Schema::hasColumn('cases', 'case_type_id')) {
        Schema::table('cases', function (Blueprint $table) {
            $table->foreignId('case_type_id')->nullable()->after('notes')->constrained('case_types')->nullOnDelete();
        });
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE sip_evidence_items MODIFY category VARCHAR(50) NOT NULL');
        }

        $now = now();

        if (DB::table('prosecutors')->count() === 0) {
            foreach ([
                ['name' => 'Andi Fajar, S.H.', 'nip' => '198505052010011001'],
                ['name' => 'Muhammad Yusuf, S.H., M.H.', 'nip' => '197912121998031002'],
                ['name' => 'Ahsan Annur, S.H.', 'nip' => '199609292012101021'],
                ['name' => 'A. Khaerul Fahmi, S.H.', 'nip' => '199505052023102002'],
                ['name' => 'Muhammad Nur, S.H.', 'nip' => '199608192023103003'],
            ] as $row) {
                DB::table('prosecutors')->insert($row + ['is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        if (DB::table('case_types')->count() === 0) {
            foreach ([
                ['name' => 'Narkotika', 'code' => 'NARKOTIKA'],
                ['name' => 'Tipikor', 'code' => 'TIPIKOR'],
                ['name' => 'Pidana Umum', 'code' => 'UMUM'],
                ['name' => 'Lalu Lintas', 'code' => 'LALU_LINTAS'],
                ['name' => 'Lainnya', 'code' => 'LAINNYA'],
            ] as $row) {
                DB::table('case_types')->insert($row + ['is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        if (DB::table('evidence_categories')->count() === 0) {
            foreach ([
                ['name' => 'Narkotika', 'code' => 'NARKOTIKA'],
                ['name' => 'Elektronik', 'code' => 'ELEKTRONIK'],
                ['name' => 'Kendaraan', 'code' => 'KENDARAAN'],
                ['name' => 'Senjata', 'code' => 'SENJATA'],
                ['name' => 'Dokumen', 'code' => 'DOKUMEN'],
                ['name' => 'Lainnya', 'code' => 'LAINNYA'],
            ] as $row) {
                DB::table('evidence_categories')->insert($row + ['is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('case_type_id');
        });
        Schema::dropIfExists('case_prosecutor');
        Schema::dropIfExists('evidence_categories');
        Schema::dropIfExists('case_types');
        Schema::dropIfExists('prosecutors');
    }
};
