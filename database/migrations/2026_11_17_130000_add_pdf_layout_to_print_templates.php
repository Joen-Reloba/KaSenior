<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('print_templates', function (Blueprint $table): void {
            $table->string('pdf_layout', 20)->default('letterhead');
        });
    }

    public function down(): void
    {
        Schema::table('print_templates', function (Blueprint $table): void {
            $table->dropColumn('pdf_layout');
        });
    }
};
