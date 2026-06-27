<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consent_types', function (Blueprint $table) {
            $table->string('version')->default('1.0')->after('active');
            $table->integer('validity_months')->nullable()->after('version');
            $table->timestamp('effective_from')->nullable()->after('validity_months');
            $table->timestamp('effective_until')->nullable()->after('effective_from');

            // A consent-type group (same slug) may have many versions, but each version is unique.
            $table->unique(['slug', 'version']);
            // Fast lookup of the current version of a group.
            $table->index(['slug', 'active']);
        });
    }

    public function down(): void
    {
        Schema::table('consent_types', function (Blueprint $table) {
            $table->dropUnique(['slug', 'version']);
            $table->dropIndex(['slug', 'active']);
            $table->dropColumn(['version', 'validity_months', 'effective_from', 'effective_until']);
        });
    }
};
