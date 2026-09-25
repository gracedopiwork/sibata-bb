<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('unit_photos')) {
            Schema::create('unit_photos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('physical_unit_id')->constrained('physical_units')->cascadeOnDelete()->unique();
                $table->string('mime', 100);
                $table->timestamps();
            });

            if (Schema::getConnection()->getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE unit_photos ADD data LONGBLOB NOT NULL');
            } else {
                Schema::table('unit_photos', function (Blueprint $table) {
                    $table->binary('data')->nullable();
                });
            }
        }

        $units = DB::table('physical_units')
            ->whereNotNull('photo_path')
            ->where('photo_path', '!=', '')
            ->get(['id', 'photo_path']);

        foreach ($units as $unit) {
            if (DB::table('unit_photos')->where('physical_unit_id', $unit->id)->exists()) {
                continue;
            }

            if (! Storage::disk('public')->exists($unit->photo_path)) {
                continue;
            }

            $contents = Storage::disk('public')->get($unit->photo_path);
            if ($contents === null || $contents === '') {
                continue;
            }

            DB::table('unit_photos')->insert([
                'physical_unit_id' => $unit->id,
                'mime' => Storage::disk('public')->mimeType($unit->photo_path) ?: 'image/jpeg',
                'data' => $contents,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_photos');
    }
};
