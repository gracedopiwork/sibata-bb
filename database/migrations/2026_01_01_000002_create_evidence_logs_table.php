<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidence_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evidence_id')->constrained('evidence_items')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('action_type', ['REGISTER', 'PINJAM', 'KEMBALI', 'RELOKASI', 'EKSEKUSI']);
            $table->string('borrower_name')->nullable();
            $table->text('purpose')->nullable();
            $table->date('expected_return_date')->nullable();
            $table->string('photo_proof_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['evidence_id', 'created_at']);
            $table->index('action_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence_logs');
    }
};
