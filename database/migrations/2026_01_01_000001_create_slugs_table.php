<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slugs', function (Blueprint $table) {
            $table->id();
            $table->string('sluggable_type');
            // Laravel's standard polymorphic key pair, so sluggable_id matches
            // the integer primary keys Eloquent models use by default. Models
            // keyed by UUID or ULID need these two columns widened to a string
            // in the published migration; see the README.
            $table->unsignedBigInteger('sluggable_id');
            $table->string('locale', 16);
            $table->string('slug');
            $table->timestamps();

            // A model can own at most one slug per locale. Also serves the
            // polymorphic relation query (sluggable_type + sluggable_id).
            $table->unique(['sluggable_type', 'sluggable_id', 'locale']);

            // Model-local slug uniqueness: within one model type no two rows
            // may share a slug in the same locale. Different model types (and
            // different locales) may reuse slugs freely. Also serves
            // locale-aware slug lookups via its leading columns.
            $table->unique(['sluggable_type', 'locale', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slugs');
    }
};
