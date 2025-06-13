<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('consent_types', function (Blueprint $table) {
            $table->string('version')->default('1.0')->after('active');
            $table->integer('validity_months')->nullable()->after('version');
            $table->timestamp('effective_from')->nullable()->after('validity_months');
            $table->timestamp('effective_until')->nullable()->after('effective_from');
        });
    }

    public function down()
    {
        Schema::table('consent_types', function (Blueprint $table) {
            $table->dropColumn(['version', 'validity_months', 'effective_from', 'effective_until']);
        });
    }
};
