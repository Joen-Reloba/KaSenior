<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('superadmin_settings', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('pin_hash')->nullable();
        });

        DB::table('superadmin_settings')->insert(['id' => 1, 'pin_hash' => null]);
    }

    public function down(): void
    {
        Schema::dropIfExists('superadmin_settings');
    }
};
