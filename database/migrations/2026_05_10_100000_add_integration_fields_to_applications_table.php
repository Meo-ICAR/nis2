<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('connector_type')->nullable()->after('client_secret');
            $table->string('webhook_token')->unique()->nullable()->after('connector_type');
            $table->json('integration_config')->nullable()->after('webhook_token');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['connector_type', 'webhook_token', 'integration_config']);
        });
    }
};
