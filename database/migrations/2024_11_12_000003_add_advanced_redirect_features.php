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
            // HTTP to HTTPS enforcement
            $table->boolean('force_https')->default(false)->after('preserve_query_string');
            
            // Case sensitivity for matching
            $table->boolean('case_sensitive')->default(false)->after('force_https');
            
            // Trailing slash handling: null (ignore), 'add', 'remove'
            $table->string('trailing_slash_mode', 10)->nullable()->after('case_sensitive');
            
            // Scheduled redirects
            $table->timestamp('active_from')->nullable()->after('is_active');
            $table->timestamp('active_until')->nullable()->after('active_from');
            
            // Index for scheduled query performance
            $table->index(['is_active', 'active_from', 'active_until']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('redirects', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'active_from', 'active_until']);
            $table->dropColumn([
                'force_https',
                'case_sensitive',
                'trailing_slash_mode',
                'active_from',
                'active_until',
            ]);
        });
    }
};
