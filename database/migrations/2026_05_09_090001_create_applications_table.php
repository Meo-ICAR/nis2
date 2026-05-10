<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 100);
            $table->string('short_name', 20)->nullable();
            $table->text('description')->nullable();
            $table->string('project', 100)->nullable();
            $table->string('category', 100)->nullable();

            // Technical Specifications
            $table->string('cpu')->nullable();
            $table->string('ram')->nullable();
            $table->string('hd')->nullable();
            $table->text('ports')->nullable();
            $table->string('runtime_type')->nullable();  // vm, container

            // NIS2 compliance fields
            $table->string('scientific_owner', 150)->nullable();
            $table->string('scientific_contact', 150)->nullable();

            $table->string('internal_technical_contact', 150)->nullable();
            $table->string('external_technical_contact', 150)->nullable();
            $table->string('external_technical_email')->nullable();

            $table->string('url', 500);

            $table->string('icon_url')->nullable();
            $table->string('url_documentation', 500)->nullable();
            $table->string('client_id', 255)->nullable();
            $table->string('client_secret', 255)->nullable();

            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_strategic')->default(false);

            $table->string('url_cockpit', 500)->nullable();
            $table->string('url_sandbox', 500)->nullable();

            // Infrastructure & technical fields
            $table->string('criticality_level')->nullable();  // essential, important, standard
            $table->string('hosting_type')->nullable();  // on-premise, cloud, hybrid, HPC
            $table->boolean('has_mfa')->default(false);
            $table->string('backup_strategy')->nullable();
            $table->string('backup_replication')->nullable();
            $table->string('url_job_anonimization_db', 500)->nullable();
            $table->string('management_url', 500)->nullable();
            $table->string('service_tag', 100)->nullable();
            $table->string('external_id', 100)->nullable();  // ID in Dell EMC / SonicWall
            $table->string('data_sensitivity')->nullable();  // common, sensitive, highly_sensitive

            $table->date('support_contract_expiry')->nullable();
            $table->text('contract_notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
