<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Immutable, append-only audit trail of every consent action.
        // This table is the legal source of truth for GDPR Art. 7(1) demonstrability:
        // it is never updated or deleted by the package, and survives consent-type deletion.
        Schema::create('consent_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('consentable_type');
            $table->string('consentable_id');

            // Nullable + nullOnDelete so erasing a consent type never destroys historical proof.
            $table->foreignId('consent_type_id')->nullable()->constrained('consent_types')->nullOnDelete();

            $table->string('consent_type_slug')->nullable();
            $table->string('consent_version')->nullable();
            $table->string('action'); // granted | revoked | renewed
            $table->timestamp('occurred_at');
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();

            // Snapshot of exactly what the subject was shown when consenting.
            $table->string('policy_url')->nullable();
            $table->string('policy_text_hash')->nullable();

            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['consentable_type', 'consentable_id'], 'cal_consentable_idx');
            $table->index(['consent_type_id', 'action'], 'cal_type_action_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_audit_logs');
    }
};
