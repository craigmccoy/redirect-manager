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
        Schema::create('redirect_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('redirect_id')->nullable()->constrained('redirects')->nullOnDelete();
            $table->string('request_domain');
            $table->string('request_path');
            $table->string('request_method')->default('GET');
            $table->string('request_url', 1000);
            $table->string('destination_url', 1000);
            $table->integer('status_code');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('referer', 1000)->nullable();
            $table->timestamp('created_at');

            // Indexes for analytics queries
            $table->index('redirect_id');
            $table->index('created_at');
            $table->index(['redirect_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('redirect_logs');
    }
};
