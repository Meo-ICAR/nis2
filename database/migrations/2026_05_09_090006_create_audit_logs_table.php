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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('event_type', 50);
            $table->foreignId('user_id')->nullable()->nullOnDelete()->constrained('users');
            // admin_id is a separate FK to users — cannot use foreignId() directly
            // because foreignId() would name the column 'admin_id' but constrained()
            // would look for an 'admins' table. We define it manually.
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('subject_type', 100)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('payload')->nullable();
            $table->string('ip_address', 45)->nullable();
            // Only created_at — no updated_at
            $table->timestamp('created_at')->nullable(false)->useCurrent();

            // Foreign key for admin_id → users.id, set null on delete
            $table->foreign('admin_id')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete();

            // Indexes
            $table->index('event_type');
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
