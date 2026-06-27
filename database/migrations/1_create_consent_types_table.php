<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consent_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // The slug identifies a consent-type "group" and is stable across versions, so it is
            // intentionally NOT unique on its own. Uniqueness is enforced per (slug, version) in a
            // later migration. A single active row per slug represents the current version.
            $table->string('slug')->index();
            $table->text('description')->nullable();
            $table->boolean('required')->default(false);
            $table->boolean('active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_types');
    }
};
