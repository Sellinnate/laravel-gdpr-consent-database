<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('consent_types', function (Blueprint $table) {
            $table->string('category')->default('other')->after('active');
        });

        DB::table('consent_types')
            ->whereIn('slug', ['technical-cookies', 'profiling', 'tracking', 'analytics'])
            ->orWhere('slug', 'like', '%cookie%')
            ->orWhere('slug', 'like', '%tracking%')
            ->orWhere('slug', 'like', '%analytics%')
            ->update(['category' => 'cookie']);
    }

    public function down()
    {
        Schema::table('consent_types', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
