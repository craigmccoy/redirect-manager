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
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('source_type')->default('url'); // 'domain' or 'url'
            $table->string('source_domain')->nullable(); // For domain-wide redirects
            $table->string('source_path')->nullable(); // For specific URL redirects
            $table->string('destination');
            $table->integer('status_code')->default(301); // 301, 302, 307, 308
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0); // Higher priority redirects are checked first
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['source_domain', 'is_active']);
            $table->index(['source_path', 'is_active']);
            $table->index(['priority', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('redirects');
    }
};
