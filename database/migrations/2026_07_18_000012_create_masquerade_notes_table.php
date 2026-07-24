<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // Create the masquerade notes table with the specified schema.
        Schema::create(config('masquerade.notes.table_name', 'masquerade_notes'), function (Blueprint $table): void {
            // Define the columns for the masquerade notes table.
            $table->id();

            // Add a UUID column for the masquerade session and index it for faster lookups.
            $table->uuid('masquerade_uuid')->index();
            
            // Add a polymorphic relationship for the author of the note, allowing for different types of authors.
            $table->nullableMorphs('author');

            // Add a text column for the note content.
            $table->text('note');

            // Add a JSON column for additional metadata related to the note, allowing for flexible data storage.
            $table->json('metadata')->nullable();

            // Add timestamp columns for created_at and updated_at to track when notes are created and modified.
            $table->timestamps();

            // Define indexes for efficient querying based on common access patterns
            $table->index(['masquerade_uuid', 'created_at']);
            $table->index(['author_type', 'author_id', 'created_at'], 'masquerade_notes_author_created_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // Drop the masquerade notes table if it exists
        Schema::dropIfExists(config('masquerade.notes.table_name', 'masquerade_notes'));
    }
};
