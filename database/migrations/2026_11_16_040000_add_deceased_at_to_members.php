<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->date('deceased_at')->nullable();
        });
        DB::table('members')->where('status', 'Deceased')->update([
            'deceased_at' => DB::raw('DATE(COALESCE(updated_at, created_at, CURRENT_TIMESTAMP))'),
        ]);
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->dropColumn('deceased_at');
        });
    }
};
