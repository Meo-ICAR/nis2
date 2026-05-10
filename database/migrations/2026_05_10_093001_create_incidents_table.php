<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('incident_type')->nullable(); // e.g., Malware, Phishing, Unauthorized Access
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['open', 'investigating', 'contained', 'resolved', 'closed'])->default('open');
            $table->timestamp('detected_at');
            $table->timestamp('resolved_at')->nullable();
            $table->boolean('acn_notified')->default(false);
            $table->timestamp('acn_notification_date')->nullable();
            $table->string('acn_protocol_number')->nullable();
            $table->text('impact_analysis')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
