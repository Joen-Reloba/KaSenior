<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table): void {
            $table->id();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('gender', 20);
            $table->date('birth_date');
            $table->string('location');
            $table->string('address')->nullable();
            $table->string('mobile', 40)->nullable();
            $table->string('emergency_contact')->nullable();
            $table->string('status', 20)->default('Active')->index();
            $table->date('registered_at');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('events', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->date('event_date');
            $table->text('description')->nullable();
            $table->unsignedInteger('amount_cents');
            $table->timestamps();
        });
        Schema::create('contributions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->foreignId('member_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('amount_cents')->default(0);
            $table->date('paid_at')->nullable();
            $table->unsignedInteger('version')->default(0);
            $table->timestamps();
            $table->unique(['event_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contributions');
        Schema::dropIfExists('events');
        Schema::dropIfExists('members');
    }
};
