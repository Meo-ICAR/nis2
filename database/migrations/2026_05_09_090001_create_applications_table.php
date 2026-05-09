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
        Schema::create('applications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('url', 500);
            $table->string('icon_url')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            // NIS2 compliance fields
            $table->string('scientific_owner', 150)->nullable();
            $table->string('internal_technical_contact', 150)->nullable();
            $table->string('external_technical_contact', 150)->nullable();
            $table->string('external_technical_email')->nullable();
            $table->date('support_contract_expiry')->nullable();
            $table->text('contract_notes')->nullable();

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
