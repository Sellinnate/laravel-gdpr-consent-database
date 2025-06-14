<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_consents', function (Blueprint $table) {
            $table->id();
            $table->string('consentable_type');
            $table->string('consentable_id');
            $table->foreignId('consent_type_id')->constrained('consent_types')->onDelete('cascade');
            $table->boolean('granted')->default(false);
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['consentable_type', 'consentable_id', 'consent_type_id'], 'uc_consentable_consent_type_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_consents');
    }
};
