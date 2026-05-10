<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('wso2_base_url')->nullable()->after('integration_config');
            $table->string('wso2_tenant_domain')->nullable()->after('wso2_base_url');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['wso2_base_url', 'wso2_tenant_domain']);
        });
    }
};
