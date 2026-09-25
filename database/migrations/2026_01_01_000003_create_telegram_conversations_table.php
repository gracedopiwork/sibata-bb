<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('telegram_user_id')->unique();
            $table->string('action');
            $table->string('step');
            $table->foreignId('evidence_id')->nullable()->constrained('evidence_items')->cascadeOnDelete();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_conversations');
    }
};
