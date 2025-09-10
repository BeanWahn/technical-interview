<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('secret_shares', function (Blueprint $table) {
            $table->dropColumn(['notes', 'max_access_count']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('secret_shares', function (Blueprint $table) {
            $table->text('notes')->nullable();
            $table->integer('max_access_count')->default(1);
        });
    }
};
