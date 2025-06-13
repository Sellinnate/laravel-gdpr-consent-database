<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('user_consents', function (Blueprint $table) {
            $table->string('consent_version')->nullable()->after('consent_type_id');
            $table->timestamp('expires_at')->nullable()->after('revoked_at');
        });
    }

    public function down()
    {
        Schema::table('user_consents', function (Blueprint $table) {
            $table->dropColumn(['consent_version', 'expires_at']);
        });
    }
};
