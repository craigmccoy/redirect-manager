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
        Schema::table('redirects', function (Blueprint $table) {
            $table->boolean('preserve_path')->default(false)->after('destination');
            $table->boolean('preserve_query_string')->default(true)->after('preserve_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('redirects', function (Blueprint $table) {
            $table->dropColumn(['preserve_path', 'preserve_query_string']);
        });
    }
};
