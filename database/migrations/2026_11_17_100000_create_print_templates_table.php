<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('format', 10);
            $table->longText('content');
            $table->unsignedSmallInteger('pdf_top')->default(45);
            $table->unsignedSmallInteger('pdf_bottom')->default(20);
            $table->unsignedSmallInteger('pdf_margin')->default(15);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_templates');
    }
};
