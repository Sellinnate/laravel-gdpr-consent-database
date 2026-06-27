<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consent_types', function (Blueprint $table) {
            // GDPR Art. 30 — records of processing activities.
            $table->string('legal_basis')->nullable()->after('category');
            $table->text('purpose')->nullable()->after('legal_basis');
            $table->string('data_controller')->nullable()->after('purpose');

            // GDPR Art. 7 — proof of informed consent: the exact policy shown for this version.
            $table->string('policy_url')->nullable()->after('data_controller');
            $table->string('policy_text_hash')->nullable()->after('policy_url');
        });
    }

    public function down(): void
    {
        Schema::table('consent_types', function (Blueprint $table) {
            $table->dropColumn([
                'legal_basis',
                'purpose',
                'data_controller',
                'policy_url',
                'policy_text_hash',
            ]);
        });
    }
};
