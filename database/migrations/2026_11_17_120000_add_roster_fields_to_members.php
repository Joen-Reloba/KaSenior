<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->string('name_extension', 40)->nullable();
            $table->string('barangay')->default('Baliok');
            $table->string('district')->default('Talomo');
            $table->string('senior_citizen_id', 100)->nullable();
            $table->date('id_issued_at')->nullable();
            $table->string('location')->default('7C')->change();
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->dropColumn(['name_extension', 'barangay', 'district', 'senior_citizen_id', 'id_issued_at']);
            $table->string('location')->change();
        });
    }
};
