<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Schema for the form definition imported from the JSON feed.
 *
 * The data is normalised into sections -> fields -> options instead of being
 * dumped as a single JSON blob, so it can be queried and indexed properly.
 * The string ids coming from the feed are kept as natural primary keys.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_sections', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index('position');
        });

        Schema::create('form_fields', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('section_id');
            $table->string('label');
            $table->string('type');               // radio_button, checkbox, text, long_text...
            $table->string('sub_type')->nullable(); // date, amount, text...
            $table->text('description')->nullable();
            $table->boolean('orm_only')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->foreign('section_id')->references('id')->on('form_sections')->cascadeOnDelete();
            $table->index('section_id');
            $table->index('type');
        });

        Schema::create('field_options', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('field_id');
            $table->string('label');
            $table->string('value')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->foreign('field_id')->references('id')->on('form_fields')->cascadeOnDelete();
            $table->index('field_id');
        });

        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('submission_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->string('field_id');
            // Stored as JSON text: scalar for text fields, array of option ids for radio/checkbox.
            $table->text('value')->nullable();
            $table->timestamps();

            $table->foreign('field_id')->references('id')->on('form_fields')->cascadeOnDelete();
            $table->index('submission_id');
            $table->index('field_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_values');
        Schema::dropIfExists('submissions');
        Schema::dropIfExists('field_options');
        Schema::dropIfExists('form_fields');
        Schema::dropIfExists('form_sections');
    }
};
