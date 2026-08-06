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
        Schema::create('occurrences', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('issue_id')->constrained()->cascadeOnDelete();
            $table->json('payload');
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['issue_id', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('occurrences');
    }
};
