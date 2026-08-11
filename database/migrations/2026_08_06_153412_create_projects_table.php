<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->string('environment');
            $table->string('token_hash')->unique();
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->timestamp('heartbeat_alerted_at')->nullable();
            $table->timestamp('issues_notified_at')->nullable();
            $table->json('pending_issue_notifications')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
