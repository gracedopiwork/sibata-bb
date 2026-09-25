<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bb_loans')) {
            Schema::create('bb_loans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('physical_unit_id')->constrained('physical_units')->cascadeOnDelete();
                $table->string('borrower_name');
                $table->date('court_date')->nullable();
                $table->text('notes')->nullable();
                $table->string('loaned_by', 100);
                $table->timestamp('loaned_at')->useCurrent();
                $table->string('loan_photo_path')->nullable();
                $table->timestamp('returned_at')->nullable();
                $table->string('returned_by', 100)->nullable();
                $table->text('return_notes')->nullable();
                $table->string('return_storage_location')->nullable();
                $table->foreignId('return_storage_location_id')->nullable()->constrained('storage_locations')->nullOnDelete();
                $table->string('return_photo_path')->nullable();
                $table->timestamps();

                $table->index(['physical_unit_id', 'returned_at']);
            });
        }

        if (! Schema::hasTable('bb_loan_photos')) {
            Schema::create('bb_loan_photos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bb_loan_id')->constrained('bb_loans')->cascadeOnDelete();
                $table->string('kind', 20);
                $table->string('mime', 100);
                $table->string('path')->nullable();
                $table->timestamps();
                $table->unique(['bb_loan_id', 'kind']);
            });

            if (Schema::getConnection()->getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE bb_loan_photos ADD data LONGBLOB NULL');
            } else {
                Schema::table('bb_loan_photos', function (Blueprint $table) {
                    $table->binary('data')->nullable();
                });
            }
        }

        $this->backfillFromMutations();
    }

    public function down(): void
    {
        Schema::dropIfExists('bb_loan_photos');
        Schema::dropIfExists('bb_loans');
    }

    private function backfillFromMutations(): void
    {
        if (! Schema::hasTable('mutations')) {
            return;
        }

        $loans = DB::table('mutations')
            ->where('mutation_type', 'PINJAM_SIDANG')
            ->orderBy('id')
            ->get();

        foreach ($loans as $mutation) {
            $exists = DB::table('bb_loans')
                ->where('physical_unit_id', $mutation->physical_unit_id)
                ->where('loaned_at', $mutation->created_at)
                ->exists();

            if ($exists) {
                continue;
            }

            $return = DB::table('mutations')
                ->where('physical_unit_id', $mutation->physical_unit_id)
                ->where('mutation_type', 'KEMBALI_GUDANG')
                ->where('id', '>', $mutation->id)
                ->orderBy('id')
                ->first();

            DB::table('bb_loans')->insert([
                'physical_unit_id' => $mutation->physical_unit_id,
                'borrower_name' => $mutation->borrower_name ?: 'Tidak tercatat',
                'court_date' => $mutation->court_date,
                'notes' => $mutation->notes,
                'loaned_by' => $mutation->handled_by,
                'loaned_at' => $mutation->created_at,
                'returned_at' => $return?->created_at,
                'returned_by' => $return?->handled_by,
                'return_notes' => $return?->notes,
                'created_at' => $mutation->created_at,
                'updated_at' => $return?->created_at ?? $mutation->created_at,
            ]);
        }
    }
};
