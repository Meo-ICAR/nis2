<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_interventions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->onDelete('cascade');
            $table->string('company_name');
            $table->string('operator_name')->nullable();
            $table->enum('intervention_type', ['hardware', 'software', 'facility'])->default('software');
            $table->text('description');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->enum('status', ['planned', 'in_progress', 'completed'])->default('completed');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_interventions');
    }
};
