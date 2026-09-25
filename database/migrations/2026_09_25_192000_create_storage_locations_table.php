<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('storage_locations')) {
            Schema::create('storage_locations', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 50)->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('physical_units', 'storage_location_id')) {
            Schema::table('physical_units', function (Blueprint $table) {
                $table->foreignId('storage_location_id')->nullable()->after('storage_location')->constrained('storage_locations')->nullOnDelete();
            });
        }

        $now = now();
        $defaults = [
            ['name' => 'Brankas PB3R Laci 01', 'code' => 'BRANKAS_01'],
            ['name' => 'Brankas PB3R Laci 02', 'code' => 'BRANKAS_02'],
            ['name' => 'Lemari Besi A-01', 'code' => 'LEMARI_A01'],
            ['name' => 'Lemari Senjata', 'code' => 'LEMARI_SENJATA'],
            ['name' => 'Parkiran BB', 'code' => 'PARKIRAN_BB'],
        ];

        foreach ($defaults as $row) {
            if (! DB::table('storage_locations')->where('code', $row['code'])->exists()) {
                DB::table('storage_locations')->insert($row + ['is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        $existing = DB::table('physical_units')
            ->whereNotNull('storage_location')
            ->where('storage_location', '!=', '')
            ->distinct()
            ->pluck('storage_location');

        foreach ($existing as $name) {
            $code = strtoupper(Str::slug((string) $name, '_'));
            $code = $code !== '' ? substr($code, 0, 50) : 'LOKASI_'.substr(md5((string) $name), 0, 8);

            if (! DB::table('storage_locations')->where('name', $name)->exists()
                && ! DB::table('storage_locations')->where('code', $code)->exists()) {
                DB::table('storage_locations')->insert([
                    'name' => $name,
                    'code' => $code,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        foreach (DB::table('storage_locations')->get() as $location) {
            DB::table('physical_units')
                ->whereNull('storage_location_id')
                ->where('storage_location', $location->name)
                ->update(['storage_location_id' => $location->id]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('physical_units', 'storage_location_id')) {
            Schema::table('physical_units', function (Blueprint $table) {
                $table->dropConstrainedForeignId('storage_location_id');
            });
        }

        Schema::dropIfExists('storage_locations');
    }
};
