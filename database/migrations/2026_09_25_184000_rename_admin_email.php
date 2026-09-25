<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('email', 'admin@kejari-wajo.go.id')
            ->update(['email' => 'admin@sibatabbwajo.my.id']);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('email', 'admin@sibatabbwajo.my.id')
            ->update(['email' => 'admin@kejari-wajo.go.id']);
    }
};
